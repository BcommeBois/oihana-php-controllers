<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\FileResponseOption;
use oihana\controllers\traits\FileTrait;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class FileTraitTest extends TestCase
{
    private object $mock;

    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use FileTrait;
        };

        $this->dir  = sys_get_temp_dir() . '/file_trait_' . uniqid();
        mkdir( $this->dir );
        $this->file = $this->dir . '/hello.txt';
        file_put_contents( $this->file , 'hello world' );
    }

    protected function tearDown(): void
    {
        foreach ( glob( $this->dir . '/*' ) ?: [] as $f )
        {
            @unlink( $f );
        }
        @rmdir( $this->dir );
    }

    /**
     * A passive Response stub with a writable body.
     */
    private function response(): ResponseInterface
    {
        $stream = $this->createStub( StreamInterface::class );
        $stream->method('write')->willReturnCallback( fn( $data ) => strlen( (string) $data ) );

        $response = $this->createStub( ResponseInterface::class );
        $response->method('getBody')->willReturn( $stream );
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnSelf();
        $response->method('withBody')->willReturnSelf();

        return $response;
    }

    public function testFileResponseStreamsFileContent(): void
    {
        // a real PSR-7 response proves the body is streamed from the file
        $response = new \Slim\Psr7\Factory\ResponseFactory()->createResponse();

        $result = $this->mock->fileResponse( null , $response , $this->file );

        $this->assertSame( 'hello world' , (string) $result->getBody() );
    }

    public function testFileResponseWithAllHeaders(): void
    {
        $response = $this->response();

        $result = $this->mock->fileResponse
        (
            null ,
            $response ,
            $this->file ,
            [
                FileResponseOption::USE_CONTENT_TYPE        => true ,
                FileResponseOption::USE_CONTENT_LENGTH      => true ,
                FileResponseOption::USE_CONTENT_DISPOSITION => true ,
                FileResponseOption::CONTENT_DISPOSITION     => 'attachment; filename=hello.txt' ,
            ]
        );

        $this->assertSame( $response , $result );
    }

    public function testFileResponseWithoutOptionalHeaders(): void
    {
        $response = $this->response();

        $result = $this->mock->fileResponse( null , $response , $this->file );

        $this->assertSame( $response , $result );
    }

    public function testFileResponseMissingFileGoesThroughCatch(): void
    {
        // assertFile() throws a FileException (extends Exception) for a missing
        // file, so the catch -> fail() path returns a 500 response cleanly.
        $response = $this->response();

        $result = $this->mock->fileResponse( null , $response , $this->dir . '/nope.txt' );

        $this->assertSame( $response , $result );
    }
}
