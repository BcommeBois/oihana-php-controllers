<?php

namespace tests\oihana\controllers\traits;

use RuntimeException;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\AppTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

use Slim\App;

final class AppTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use AppTrait;
        };
    }

    private function appStub( string $basePath = '/myapp' ): App
    {
        $app = $this->createStub( App::class );
        $app->method('getBasePath')->willReturn( $basePath );
        return $app;
    }

    public function testInitializeAppFromInit(): void
    {
        $app    = $this->appStub();
        $result = $this->mock->initializeApp([ ControllerParam::APP => $app ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( '/myapp' , $this->mock->getBasePath() );
    }

    public function testInitializeAppFromContainer(): void
    {
        $app = $this->appStub( '/from-container' );

        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( $app );

        $this->mock->initializeApp( [] , $container );

        $this->assertSame( '/from-container' , $this->mock->getBasePath() );
    }

    public function testInitializeAppThrowsWhenNoAppAvailable(): void
    {
        $this->expectException( RuntimeException::class );
        $this->mock->initializeApp();
    }

    public function testInitializeAppThrowsWhenContainerLacksApp(): void
    {
        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( false );

        $this->expectException( RuntimeException::class );
        $this->mock->initializeApp( [] , $container );
    }

    public function testGetUrlCombinesBaseUrlBasePathAndPath(): void
    {
        $this->mock->initializeApp([ ControllerParam::APP => $this->appStub( '/myapp' ) ]);
        $this->mock->baseUrl = 'https://example.com';

        $this->assertSame( 'https://example.com/myapp/users' , $this->mock->getUrl( 'users' ) );
    }

    public function testGetUrlWithQueryParams(): void
    {
        $this->mock->initializeApp([ ControllerParam::APP => $this->appStub( '' ) ]);
        $this->mock->baseUrl = '/api';

        $this->assertSame( '/api/items?page=2' , $this->mock->getUrl( 'items' , [ 'page' => 2 ] ) );
    }
}
