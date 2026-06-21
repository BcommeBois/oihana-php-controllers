<?php

namespace tests\oihana\controllers\traits;

use RuntimeException;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\RouterTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Interfaces\RouteParserInterface;

final class RouterTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        // redirectResponse() lives on Controller, so the host supplies a stand-in
        // that records its arguments; urlFor() is protected, hence the proxy.
        $this->mock = new class
        {
            use RouterTrait;

            public array $redirectArgs = [] ;

            public function redirectResponse( Response $response , string $url , int $status = 302 ): Response
            {
                $this->redirectArgs = [ $url , $status ] ;
                return $response ;
            }

            public function callUrlFor( string $routeName ): string
            {
                return $this->urlFor( $routeName );
            }
        };
    }

    private function routerStub( string $url = '/resolved' ): RouteParserInterface
    {
        $router = $this->createStub( RouteParserInterface::class );
        $router->method('urlFor')->willReturn( $url );
        return $router;
    }

    public function testInitializeRouterParserFromInit(): void
    {
        $result = $this->mock->initializeRouterParser([ ControllerParam::ROUTER => $this->routerStub() ]);

        $this->assertSame( $this->mock , $result );
        $this->mock->baseUrl = '/api';
        $this->assertSame( '/api/resolved' , $this->mock->callUrlFor( 'home' ) );
    }

    public function testInitializeRouterParserFromContainer(): void
    {
        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( $this->routerStub( '/from-container' ) );

        $this->mock->initializeRouterParser( [] , $container );

        $this->assertSame( '/from-container' , $this->mock->callUrlFor( 'home' ) );
    }

    public function testInitializeRouterParserThrowsWhenUnresolved(): void
    {
        $this->expectException( RuntimeException::class );
        $this->mock->initializeRouterParser();
    }

    public function testUrlForPrependsBaseUrl(): void
    {
        $this->mock->initializeRouterParser([ ControllerParam::ROUTER => $this->routerStub( '/posts' ) ]);
        $this->mock->baseUrl = 'https://example.com';

        $this->assertSame( 'https://example.com/posts' , $this->mock->callUrlFor( 'posts' ) );
    }

    public function testRedirectForDelegatesToRedirectResponse(): void
    {
        $this->mock->initializeRouterParser([ ControllerParam::ROUTER => $this->routerStub( '/target' ) ]);

        $response = $this->createStub( Response::class );
        $result   = $this->mock->redirectFor( $response , 'named.route' , [ 'id' => 1 ] , 301 );

        $this->assertSame( $response , $result );
        $this->assertSame( [ '/target' , 301 ] , $this->mock->redirectArgs );
    }
}
