<?php

namespace oihana\controllers\traits ;

use RuntimeException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;

use oihana\controllers\enums\ControllerParam;

use Slim\Interfaces\RouteParserInterface;

/**
 * Provides helper methods to manage the application's router and base URL.
 *
 * This trait allows you to:
 * - initialize the internal router parser from an array or a DI container,
 * - generate URLs for named routes including the base URL,
 * - redirect responses to named routes.
 *
 * Note: You can define a `baseUrl` in the DI container to be used across all controllers.
 *
 * @package oihana\controllers\traits
 */
trait RouterTrait
{
    use BaseUrlTrait ;

    /**
     * The router parser instance.
     *
     * @var RouteParserInterface
     */
    protected RouteParserInterface $router ;

    /**
     * Initializes the internal `router` property.
     *
     * The router instance can be provided in the `$init` array or fetched
     * from the DI container if available.
     *
     * @param array $init Optional initialization array
     * @param ContainerInterface|null $container Optional DI container
     * @return static Returns the current instance for method chaining
     * @throws NotFoundExceptionInterface If the requested container entry is not found
     * @throws ContainerExceptionInterface If the container throws an error during access
     * @throws RuntimeException If no router instance can be resolved
     */
    public function initializeRouterParser( array $init = [] , ?ContainerInterface $container = null  ):static
    {
        $router = $init[ ControllerParam::ROUTER ] ?? null;

        if( $container instanceof ContainerInterface && $container->has( RouteParserInterface::class ) )
        {
            $router = $container->get( RouteParserInterface::class ) ;
        }

        if( !$router instanceof RouteParserInterface )
        {
            throw new RuntimeException( 'A controller `app` property must be defined.' ) ;
        }

        $this->router = $router ;

        return $this ;
    }

    /**
     * Redirects the response to a named route.
     *
     * The route URL is generated using `$this->router->urlFor()`.
     *
     * @param Response $response PSR-7 response instance
     * @param string $name Name of the route
     * @param array $params Optional associative array of route parameters
     * @param int $status HTTP redirect status code (default 302)
     *
     * @return Response PSR-7 response with redirect headers
     */
    public function redirectFor( Response $response , string $name , array $params = [] , int $status = 302 ) : Response
    {
        return $this->redirectResponse( $response , $this->router->urlFor( $name , $params ) , $status ) ;
    }

    /**
     * Builds the full URL for a named route including the base URL.
     *
     * Note: Assumes the `$router` property is initialized and provides a `urlFor()` method.
     *
     * @param string $routeName Name of the route
     *
     * @return string Full URL including base URL and route path
     */
    protected function urlFor( string $routeName ) :string
    {
        return $this->baseUrl . $this->router->urlFor( $routeName ) ;
    }
}