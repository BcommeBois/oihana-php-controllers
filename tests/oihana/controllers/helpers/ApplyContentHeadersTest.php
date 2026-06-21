<?php

namespace tests\oihana\controllers\helpers;

use oihana\controllers\enums\FileResponseOption;
use oihana\enums\http\HttpHeader;

use PHPUnit\Framework\TestCase;

use Slim\Psr7\Factory\ResponseFactory;

use function oihana\controllers\helpers\applyContentHeaders;

final class ApplyContentHeadersTest extends TestCase
{
    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->dir  = sys_get_temp_dir() . '/apply_headers_' . uniqid();
        mkdir( $this->dir );
        $this->file = $this->dir . '/hello.txt';
        file_put_contents( $this->file , 'hello world' );
    }

    protected function tearDown(): void
    {
        @unlink( $this->file );
        @rmdir( $this->dir );
    }

    private function response()
    {
        return new ResponseFactory()->createResponse() ;
    }

    public function testDefaultOnAppliesAllHeaders(): void
    {
        $result = applyContentHeaders( $this->response() , $this->file ) ;

        $this->assertSame( 'text/plain' , $result->getHeaderLine( HttpHeader::CONTENT_TYPE ) );
        $this->assertSame( (string) strlen( 'hello world' ) , $result->getHeaderLine( HttpHeader::CONTENT_LENGTH ) );
        $this->assertSame( 'attachment; filename=hello.txt' , $result->getHeaderLine( HttpHeader::CONTENT_DISPOSITION ) );
    }

    public function testExplicitContentTypeWins(): void
    {
        $result = applyContentHeaders( $this->response() , $this->file , 'application/json' ) ;

        $this->assertSame( 'application/json' , $result->getHeaderLine( HttpHeader::CONTENT_TYPE ) );
    }

    public function testDefaultOffAppliesNothing(): void
    {
        $result = applyContentHeaders( $this->response() , $this->file , null , [] , defaultOn: false ) ;

        $this->assertFalse( $result->hasHeader( HttpHeader::CONTENT_TYPE ) );
        $this->assertFalse( $result->hasHeader( HttpHeader::CONTENT_LENGTH ) );
        $this->assertFalse( $result->hasHeader( HttpHeader::CONTENT_DISPOSITION ) );
    }

    public function testPerOptionOverridesAndCustomDisposition(): void
    {
        $result = applyContentHeaders
        (
            $this->response() ,
            $this->file ,
            'application/octet-stream' ,
            [
                FileResponseOption::USE_CONTENT_TYPE    => false ,
                FileResponseOption::CONTENT_DISPOSITION => 'inline; filename=hello.txt' ,
            ]
        ) ;

        $this->assertFalse( $result->hasHeader( HttpHeader::CONTENT_TYPE ) ); // toggled off
        $this->assertSame( 'inline; filename=hello.txt' , $result->getHeaderLine( HttpHeader::CONTENT_DISPOSITION ) );
        $this->assertSame( (string) strlen( 'hello world' ) , $result->getHeaderLine( HttpHeader::CONTENT_LENGTH ) );
    }
}
