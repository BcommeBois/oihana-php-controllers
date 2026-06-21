<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\RangeTrait;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Psr7\Factory\ResponseFactory;

final class RangeTraitTest extends TestCase
{
    private object $mock;

    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use RangeTrait;
        };

        $this->dir  = sys_get_temp_dir() . '/range_trait_' . uniqid();
        mkdir( $this->dir );
        $this->file = $this->dir . '/hello.txt';
        file_put_contents( $this->file , 'hello world' ); // 11 bytes
    }

    protected function tearDown(): void
    {
        @unlink( $this->file );
        @rmdir( $this->dir );
    }

    private function response(): ResponseInterface
    {
        return new ResponseFactory()->createResponse() ;
    }

    private function request( string $range ): Request
    {
        $request = $this->createStub( Request::class ) ;
        $request->method('getHeaderLine')->willReturn( $range ) ;
        return $request ;
    }

    public function testFullContentWhenNoRange(): void
    {
        $result = $this->mock->rangeFileResponse( null , $this->response() , $this->file ) ;

        $this->assertSame( 200 , $result->getStatusCode() );
        $this->assertSame( 'bytes' , $result->getHeaderLine( HttpHeader::ACCEPT_RANGES ) );
        $this->assertSame( 'hello world' , (string) $result->getBody() );
    }

    public function testPartialContentClosedRange(): void
    {
        $result = $this->mock->rangeFileResponse( $this->request( 'bytes=0-4' ) , $this->response() , $this->file ) ;

        $this->assertSame( HttpStatusCode::PARTIAL_CONTENT , $result->getStatusCode() );
        $this->assertSame( 'bytes 0-4/11' , $result->getHeaderLine( HttpHeader::CONTENT_RANGE ) );
        $this->assertSame( '5' , $result->getHeaderLine( HttpHeader::CONTENT_LENGTH ) );
        $this->assertSame( 'hello' , (string) $result->getBody() );
    }

    public function testPartialContentOpenEnded(): void
    {
        $result = $this->mock->rangeFileResponse( $this->request( 'bytes=6-' ) , $this->response() , $this->file ) ;

        $this->assertSame( 'bytes 6-10/11' , $result->getHeaderLine( HttpHeader::CONTENT_RANGE ) );
        $this->assertSame( 'world' , (string) $result->getBody() );
    }

    public function testPartialContentSuffix(): void
    {
        $result = $this->mock->rangeFileResponse( $this->request( 'bytes=-5' ) , $this->response() , $this->file ) ;

        $this->assertSame( 'bytes 6-10/11' , $result->getHeaderLine( HttpHeader::CONTENT_RANGE ) );
        $this->assertSame( 'world' , (string) $result->getBody() );
    }

    public function testUnsatisfiableRangeReturns416(): void
    {
        $result = $this->mock->rangeFileResponse( $this->request( 'bytes=99999-' ) , $this->response() , $this->file ) ;

        $this->assertSame( HttpStatusCode::RANGE_NOT_SATISFIABLE , $result->getStatusCode() );
        $this->assertSame( 'bytes */11' , $result->getHeaderLine( HttpHeader::CONTENT_RANGE ) );
    }

    public function testMultiRangeFallsBackToFullContent(): void
    {
        $result = $this->mock->rangeFileResponse( $this->request( 'bytes=0-2,5-7' ) , $this->response() , $this->file ) ;

        $this->assertSame( 200 , $result->getStatusCode() );
        $this->assertSame( 'hello world' , (string) $result->getBody() );
    }

    public function testMissingFileReturns500(): void
    {
        $result = $this->mock->rangeFileResponse( null , $this->response() , $this->dir . '/nope.txt' ) ;

        $this->assertSame( 500 , $result->getStatusCode() );
    }
}
