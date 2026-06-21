<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ApiTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

final class ApiTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use ApiTrait;

            public function api(): array
            {
                return $this->api;
            }
        };
    }

    public function testInitializeApiFromInit(): void
    {
        $api    = [ 'name' => 'my-api' , 'version' => 2 ];
        $result = $this->mock->initializeApi([ ControllerParam::API => $api ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( $api , $this->mock->api() );
    }

    public function testInitializeApiFromContainer(): void
    {
        $api = [ 'from' => 'container' ];

        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( $api );

        $this->mock->initializeApi( [] , $container );

        $this->assertSame( $api , $this->mock->api() );
    }

    public function testContainerTakesPrecedenceOverInit(): void
    {
        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( [ 'winner' => 'container' ] );

        $this->mock->initializeApi( [ ControllerParam::API => [ 'winner' => 'init' ] ] , $container );

        $this->assertSame( [ 'winner' => 'container' ] , $this->mock->api() );
    }

    public function testNonArrayValueFallsBackToEmptyArray(): void
    {
        $this->mock->initializeApi([ ControllerParam::API => 'not-an-array' ]);
        $this->assertSame( [] , $this->mock->api() );
    }

    public function testEmptyByDefault(): void
    {
        $this->mock->initializeApi();
        $this->assertSame( [] , $this->mock->api() );
    }
}
