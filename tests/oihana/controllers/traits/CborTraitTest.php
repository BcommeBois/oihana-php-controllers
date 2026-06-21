<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\CborTrait;
use oihana\core\options\ArrayOption;
use oihana\enums\http\HttpHeader;
use oihana\files\enums\FileMimeType;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

final class CborTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use CborTrait;
        };
    }

    // ----------------------------------------------------------- init

    public function testInitializeCborOptionsFromInit(): void
    {
        $options = [ ArrayOption::REDUCE => false , 'custom' => 1 ];
        $result  = $this->mock->initializeCborOptions([ ControllerParam::CBOR_SERIALIZE_OPTIONS => $options ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( $options , $this->mock->cborSerializeOptions );
    }

    public function testInitializeCborOptionsFromContainer(): void
    {
        $options = [ 'from' => 'container' ];

        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( $options );

        // an empty array forces the empty()/has() container lookup
        $this->mock->initializeCborOptions( [ ControllerParam::CBOR_SERIALIZE_OPTIONS => [] ] , $container );

        $this->assertSame( $options , $this->mock->cborSerializeOptions );
    }

    public function testInitializeCborOptionsNonArrayKeepsDefault(): void
    {
        $default = $this->mock->cborSerializeOptions;
        $this->mock->initializeCborOptions([ ControllerParam::CBOR_SERIALIZE_OPTIONS => 'nope' ]);

        $this->assertSame( $default , $this->mock->cborSerializeOptions );
    }

    // ----------------------------------------------------------- cborResponse

    public function testCborResponseSetsBodyHeadersAndStatus(): void
    {
        $captured = [] ;

        $response = $this->createStub( ResponseInterface::class );
        $response->method('withBody')->willReturnSelf();
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnCallback
        (
            function( $name , $value ) use ( &$captured , $response )
            {
                $captured[ $name ] = $value ;
                return $response ;
            }
        );

        // Make the output buffer non-empty so the ob_clean() branch executes.
        ob_start();
        echo 'dirty';
        $result = $this->mock->cborResponse( $response , [ 'foo' => 'bar' ] , 201 );
        if ( ob_get_level() > 0 )
        {
            @ob_end_clean();
        }

        $this->assertSame( $response , $result );
        $this->assertSame( FileMimeType::CBOR , $captured[ HttpHeader::CONTENT_TYPE ] );
        $this->assertArrayHasKey( HttpHeader::CONTENT_LENGTH , $captured );
    }
}
