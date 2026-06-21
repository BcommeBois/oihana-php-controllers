<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\StatusTrait;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

use oihana\enums\Output;

final class StatusTraitTest extends TestCase
{
    private object $mock;
    private ResponseInterface $response;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use StatusTrait;

            public function getCurrentPath
            (
                ?ServerRequestInterface $request = null,
                array                   $params = []
            )
            : string
            {
                return '/current/path';
            }
        };

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('write')->willReturnCallback(fn( $data) => strlen((string)$data));

        $this->response = $this->createStub(ResponseInterface::class);
        $this->response->method('getBody')->willReturn( $stream );
        $this->response->method('withStatus')->willReturnSelf();
        $this->response->method('withHeader')->willReturnSelf();
    }

    public function testFailReturnsResponseWithDefaultStatus()
    {
        $response = $this->mock->fail(null , $this->response);

        $this->assertSame($this->response, $response);
    }

    public function testFailReturnsResponseWithCustomCodeAndDetails()
    {
        $response = $this->mock->fail
        (
            null ,
            $this->response,
            406,
            'Validation failed',
            ['field' => 'required']
        );

        $this->assertSame($this->response, $response);
    }

    public function testStatusReturnsResponseWithMessageAndCode()
    {
        $response = $this->mock->status( null , $this->response, 'Bad request', 400);

        $this->assertSame($this->response, $response);
    }

    public function testStatusReturnsNullWhenResponseIsNull()
    {
        $result = $this->mock->status(null , null, 'Any message' );
        $this->assertNull($result);
    }

    public function testSuccessReturnsResponseWithDataAndDefaults()
    {
        $data = ['foo' => 'bar'];

        $response = $this->mock->success(null , $this->response, $data);

        $this->assertSame($this->response, $response);
    }

    public function testSuccessReturnsDataDirectlyWhenResponseIsNull()
    {
        $data = ['foo' => 'bar'];
        $result = $this->mock->success(null , null, $data);

        $this->assertSame($data, $result);
    }

    public function testSuccessMergesInitOptionsIntoResponse()
    {
        $data = ['item' => 123];
        $init =
        [
            Output::COUNT    => 10,
            Output::LIMIT    => 5,
            Output::OFFSET   => 2,
            Output::STATUS   => 201,
            Output::URL      => '/custom/url',
            Output::OWNER    => ['id' => 1],
            Output::POSITION => 0,
            Output::TOTAL    => 50,
            Output::OPTIONS  => ['extra' => 'value'],
            Output::PARAMS   => ['a' => 1]
        ];

        $response = $this->mock->success(null , $this->response, $data, $init);

        $this->assertSame($this->response, $response);
    }

    public function testSuccessWithNewBodyResetsBodyBeforeWriting()
    {
        // Stream that records its write history so we can assert
        // it received only ONE envelope after the helper runs.
        $writes = [] ;

        $freshStream = $this->createStub( StreamInterface::class ) ;
        $freshStream->method('write')->willReturnCallback
        (
            function( $data ) use ( &$writes )
            {
                $writes[] = (string) $data ;
                return strlen( (string) $data ) ;
            }
        ) ;

        $response = $this->createStub( ResponseInterface::class ) ;
        $response->method('getBody')->willReturn( $freshStream ) ;
        $response->method('withBody')->willReturnSelf() ;
        $response->method('withStatus')->willReturnSelf() ;
        $response->method('withHeader')->willReturnSelf() ;

        $result = $this->mock->successWithNewBody( null , $response , [ 'foo' => 'bar' ] ) ;

        $this->assertSame( $response , $result ) ;
        $this->assertCount( 1 , $writes , 'body must contain exactly one JSON envelope' ) ;
        $this->assertJson( $writes[0] ) ;
        $this->assertStringNotContainsString( '}{' , $writes[0] ) ;
    }

    public function testSuccessWithNewBodyReturnsDataWhenResponseIsNull()
    {
        $data = [ 'k' => 'v' ] ;
        $this->assertSame( $data , $this->mock->successWithNewBody( null , null , $data ) ) ;
    }

    public function testWithFreshBodyReturnsNullWhenResponseIsNull()
    {
        $this->assertNull( $this->mock->withFreshBody( null ) ) ;
    }

    public function testWithFreshBodyCallsWithBodyOnResponse()
    {
        $passedStream = null ;

        $response = $this->createStub( ResponseInterface::class ) ;
        $response->method('withBody')->willReturnCallback
        (
            function( $stream ) use ( &$passedStream , $response )
            {
                $passedStream = $stream ;
                return $response ;
            }
        ) ;

        $result = $this->mock->withFreshBody( $response ) ;

        $this->assertSame( $response , $result ) ;
        $this->assertInstanceOf( StreamInterface::class , $passedStream ) ;
        $this->assertSame( '' , (string) $passedStream , 'stream passed to withBody must be empty' ) ;
    }

    public function testFailLogsErrorWithDetailsWhenLoggable()
    {
        $logged = null ;

        $logger = $this->createMock( LoggerInterface::class ) ;
        $logger->expects( $this->once() )
               ->method('error')
               ->willReturnCallback( function( $message ) use ( &$logged ) { $logged = (string) $message ; } ) ;

        $this->mock->loggable = true ;
        $this->mock->setLogger( $logger ) ;

        $this->mock->fail( null , $this->response , 404 , 'Resource missing' ) ;

        $this->assertStringContainsString( '404'              , $logged ) ;
        $this->assertStringContainsString( 'Resource missing' , $logged ) ;
    }

    public function testFailLogsErrorWithoutDetailsWhenLoggable()
    {
        $logged = null ;

        $logger = $this->createMock( LoggerInterface::class ) ;
        $logger->expects( $this->once() )
               ->method('error')
               ->willReturnCallback( function( $message ) use ( &$logged ) { $logged = (string) $message ; } ) ;

        $this->mock->loggable = true ;
        $this->mock->setLogger( $logger ) ;

        $this->mock->fail( null , $this->response , 500 ) ;

        // exercises the hasDetails === false branch: still logs the status line
        $this->assertStringContainsString( '500' , $logged ) ;
    }

    public function testWithFreshBodyComposesWithFail()
    {
        // Verifies the documented composition pattern:
        //   $this->fail( $req , $this->withFreshBody( $resp ) , 502 , ... )
        // i.e. withFreshBody can sit transparently between request and any
        // other StatusTrait helper.
        $response = $this->createStub( ResponseInterface::class ) ;
        $response->method('getBody')->willReturn( $this->createStub( StreamInterface::class ) ) ;
        $response->method('withBody')->willReturnSelf() ;
        $response->method('withStatus')->willReturnSelf() ;
        $response->method('withHeader')->willReturnSelf() ;

        $result = $this->mock->fail
        (
            null ,
            $this->mock->withFreshBody( $response ) ,
            502 ,
            'zitadel_sync_failed'
        ) ;

        $this->assertSame( $response , $result ) ;
    }
}