<?php

namespace tests\oihana\controllers;

use DI\Container;

use oihana\controllers\Controller;
use oihana\controllers\enums\ControllerParam;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\App;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

final class ControllerTest extends TestCase
{
    /**
     * Builds a concrete instance of the abstract Controller with the minimal
     * dependencies its constructor requires (a Slim App and a route parser).
     */
    private function controller(): Controller
    {
        $container = new Container();

        $app = $this->createStub( App::class );
        $app->method('getBasePath')->willReturn( '' );

        $router = $this->createStub( RouteParserInterface::class );

        return new class( $container , [ ControllerParam::APP => $app , ControllerParam::ROUTER => $router ] ) extends Controller {} ;
    }

    /**
     * A request carrying the Slim routing attributes consumed by RouteContext::fromRequest().
     */
    private function routedRequest( ?RoutingResults $routingResults = null , ?RouteInterface $route = null ): Request
    {
        $routingResults ??= $this->createStub( RoutingResults::class );
        $routeParser      = $this->createStub( RouteParserInterface::class );

        $request = $this->createStub( Request::class );
        $request->method('getAttribute')->willReturnCallback
        (
            fn( $name , $default = null ) => match( $name )
            {
                RouteContext::ROUTE           => $route ,
                RouteContext::ROUTE_PARSER    => $routeParser ,
                RouteContext::ROUTING_RESULTS => $routingResults ,
                default                       => $default ,
            }
        );

        return $request ;
    }

    public function testConstructorInitializesAllTraits(): void
    {
        $controller = $this->controller();

        $this->assertInstanceOf( Controller::class , $controller );
        $this->assertSame( '' , $controller->getBasePath() );
    }

    public function testGetAllowedMethodsReturnsEmptyArrayWithoutRequest(): void
    {
        $this->assertSame( [] , $this->controller()->getAllowedMethods( null ) );
    }

    public function testGetAllowedMethodsFromRouteContext(): void
    {
        $routingResults = $this->createStub( RoutingResults::class );
        $routingResults->method('getAllowedMethods')->willReturn( [ 'GET' , 'POST' ] );

        $request = $this->routedRequest( routingResults: $routingResults );

        $this->assertSame( [ 'GET' , 'POST' ] , $this->controller()->getAllowedMethods( $request ) );
    }

    public function testGetRouteReturnsNullWithoutRequest(): void
    {
        $this->assertNull( $this->controller()->getRoute( null ) );
    }

    public function testGetRouteFromRouteContext(): void
    {
        $route   = $this->createStub( RouteInterface::class );
        $request = $this->routedRequest( route: $route );

        $this->assertSame( $route , $this->controller()->getRoute( $request ) );
    }

    public function testRedirectResponseSetsLocationAndStatus(): void
    {
        $captured = [] ;

        $response = $this->createStub( Response::class );
        $response->method('withHeader')->willReturnCallback
        (
            function( $name , $value ) use ( &$captured , $response )
            {
                $captured[ $name ] = $value ;
                return $response ;
            }
        );
        $response->method('withStatus')->willReturnCallback
        (
            function( $status ) use ( &$captured , $response )
            {
                $captured[ 'status' ] = $status ;
                return $response ;
            }
        );

        $result = $this->controller()->redirectResponse( $response , '/target' , 301 );

        $this->assertSame( $response , $result );
        $this->assertSame( '/target' , $captured[ \oihana\enums\http\HttpHeader::LOCATION ] );
        $this->assertSame( 301 , $captured[ 'status' ] );
    }
}
