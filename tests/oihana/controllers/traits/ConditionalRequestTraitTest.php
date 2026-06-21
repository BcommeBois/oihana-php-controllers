<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ConditionalRequestOption;
use oihana\controllers\traits\ConditionalRequestTrait;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Psr7\Factory\ResponseFactory;

use function oihana\controllers\helpers\computeETag;

final class ConditionalRequestTraitTest extends TestCase
{
    private object $mock;

    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use ConditionalRequestTrait;
        };

        $this->dir  = sys_get_temp_dir() . '/conditional_trait_' . uniqid();
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

    /**
     * @param array<string,string> $headers Header name => value (others resolve to '').
     */
    private function request( array $headers ): Request
    {
        $request = $this->createStub( Request::class ) ;
        $request->method('getHeaderLine')->willReturnCallback( fn( string $name ) : string => $headers[ $name ] ?? '' ) ;
        return $request ;
    }

    private function lastModified(): string
    {
        return gmdate( 'D, d M Y H:i:s' , filemtime( $this->file ) ) . ' GMT' ;
    }

    public function testFullContentWhenNoConditionalHeaders(): void
    {
        $result = $this->mock->conditionalFileResponse( null , $this->response() , $this->file ) ;

        $this->assertSame( 200 , $result->getStatusCode() );
        $this->assertSame( 'hello world' , (string) $result->getBody() );
        $this->assertSame( computeETag( $this->file ) , $result->getHeaderLine( HttpHeader::ETAG ) );
        $this->assertSame( $this->lastModified() , $result->getHeaderLine( HttpHeader::LAST_MODIFIED ) );
        $this->assertSame( '11' , $result->getHeaderLine( HttpHeader::CONTENT_LENGTH ) );
    }

    public function testNotModifiedWhenIfNoneMatchMatches(): void
    {
        $etag    = computeETag( $this->file ) ;
        $request = $this->request( [ HttpHeader::IF_NONE_MATCH => $etag ] ) ;

        $result = $this->mock->conditionalFileResponse( $request , $this->response() , $this->file ) ;

        $this->assertSame( HttpStatusCode::NOT_MODIFIED , $result->getStatusCode() );
        $this->assertSame( '' , (string) $result->getBody() );
        $this->assertSame( $etag , $result->getHeaderLine( HttpHeader::ETAG ) );
        $this->assertSame( '' , $result->getHeaderLine( HttpHeader::CONTENT_LENGTH ) );
    }

    public function testNotModifiedWhenIfNoneMatchIsWildcard(): void
    {
        $request = $this->request( [ HttpHeader::IF_NONE_MATCH => '*' ] ) ;

        $result = $this->mock->conditionalFileResponse( $request , $this->response() , $this->file ) ;

        $this->assertSame( HttpStatusCode::NOT_MODIFIED , $result->getStatusCode() );
    }

    public function testFullContentWhenIfNoneMatchDoesNotMatch(): void
    {
        $request = $this->request( [ HttpHeader::IF_NONE_MATCH => '"stale"' ] ) ;

        $result = $this->mock->conditionalFileResponse( $request , $this->response() , $this->file ) ;

        $this->assertSame( 200 , $result->getStatusCode() );
        $this->assertSame( 'hello world' , (string) $result->getBody() );
    }

    public function testNotModifiedWhenIfModifiedSinceNotOlder(): void
    {
        $request = $this->request( [ HttpHeader::IF_MODIFIED_SINCE => $this->lastModified() ] ) ;

        $result = $this->mock->conditionalFileResponse( $request , $this->response() , $this->file ) ;

        $this->assertSame( HttpStatusCode::NOT_MODIFIED , $result->getStatusCode() );
    }

    public function testFullContentWhenIfModifiedSinceOlder(): void
    {
        $older   = gmdate( 'D, d M Y H:i:s' , filemtime( $this->file ) - 3600 ) . ' GMT' ;
        $request = $this->request( [ HttpHeader::IF_MODIFIED_SINCE => $older ] ) ;

        $result = $this->mock->conditionalFileResponse( $request , $this->response() , $this->file ) ;

        $this->assertSame( 200 , $result->getStatusCode() );
        $this->assertSame( 'hello world' , (string) $result->getBody() );
    }

    public function testIfNoneMatchTakesPrecedenceOverIfModifiedSince(): void
    {
        // If-Modified-Since alone would yield 304, but a non-matching If-None-Match wins -> 200.
        $request = $this->request
        ([
            HttpHeader::IF_NONE_MATCH    => '"stale"' ,
            HttpHeader::IF_MODIFIED_SINCE => $this->lastModified() ,
        ]) ;

        $result = $this->mock->conditionalFileResponse( $request , $this->response() , $this->file ) ;

        $this->assertSame( 200 , $result->getStatusCode() );
    }

    public function testWeakOption(): void
    {
        $result = $this->mock->conditionalFileResponse( null , $this->response() , $this->file , [ ConditionalRequestOption::WEAK => true ] ) ;

        $this->assertStringStartsWith( 'W/' , $result->getHeaderLine( HttpHeader::ETAG ) );
    }

    public function testHashContentOption(): void
    {
        $result = $this->mock->conditionalFileResponse( null , $this->response() , $this->file , [ ConditionalRequestOption::HASH_CONTENT => true ] ) ;

        $this->assertSame( '"' . md5_file( $this->file ) . '"' , $result->getHeaderLine( HttpHeader::ETAG ) );
    }

    public function testMissingFileReturns500(): void
    {
        $result = $this->mock->conditionalFileResponse( null , $this->response() , $this->dir . '/nope.txt' ) ;

        $this->assertSame( 500 , $result->getStatusCode() );
    }
}
