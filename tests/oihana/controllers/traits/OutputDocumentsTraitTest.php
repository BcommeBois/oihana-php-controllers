<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\OutputDocumentsTrait;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class OutputDocumentsTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        // outputDocuments(), documentsResponse() and getDocumentUrl() are protected.
        $this->mock = new class
        {
            use OutputDocumentsTrait;

            public function callOutputDocuments( ?Request $request , ?Response $response , ?array $documents , array $params = [] , ?array $options = null ): array|object|null
            {
                return $this->outputDocuments( $request , $response , $documents , $params , $options );
            }

            public function callGetDocumentUrl( ?Request $request = null , array $params = [] ): string
            {
                return $this->getDocumentUrl( $request , $params );
            }
        };
    }

    private function request( string $path = '/documents' ): Request
    {
        $uri = $this->createStub( UriInterface::class );
        $uri->method('getPath')->willReturn( $path );

        $request = $this->createStub( Request::class );
        $request->method('getUri')->willReturn( $uri );
        $request->method('getHeaderLine')->willReturn( '' );

        return $request;
    }

    private function response(): Response
    {
        $stream = $this->createStub( StreamInterface::class );
        $stream->method('write')->willReturnCallback( fn( $data ) => strlen( (string) $data ) );

        $response = $this->createStub( Response::class );
        $response->method('getBody')->willReturn( $stream );
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnSelf();

        return $response;
    }

    public function testOutputDocumentsWrapsInResponseWhenResponseProvided(): void
    {
        $response  = $this->response();
        $documents = [ [ 'id' => 1 ] , [ 'id' => 2 ] ];

        $result = $this->mock->callOutputDocuments
        (
            $this->request() ,
            $response ,
            $documents ,
            [ 'page' => 1 , 'empty' => null ] ,
            [ 'extra' => true ]
        );

        $this->assertSame( $response , $result );
    }

    public function testOutputDocumentsReturnsRawDocumentsWhenNoResponse(): void
    {
        $documents = [ [ 'id' => 1 ] ];

        $result = $this->mock->callOutputDocuments( $this->request() , null , $documents );

        $this->assertSame( $documents , $result );
    }

    public function testGetDocumentUrlUsesCurrentPath(): void
    {
        $this->mock->baseUrl = '/api';

        $this->assertSame( '/api/documents' , $this->mock->callGetDocumentUrl( $this->request( '/documents' ) ) );
    }
}
