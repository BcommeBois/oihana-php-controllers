<?php

namespace oihana\controllers\traits ;

use RuntimeException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use Slim\App;

use oihana\controllers\enums\ControllerParam;

use function oihana\core\strings\formatRequestArgs;
use function oihana\files\path\joinPaths;

/**
 * Trait providing helpers to manage the application instance and generate URLs.
 *
 * This trait allows controllers to access the Slim App instance, retrieve
 * the application's base path, and build URLs with optional query parameters.
 *
 * Note: You can define a `baseUrl` in the DI container to use it consistently
 * across all controllers.
 */
trait AppTrait
{
    use BaseUrlTrait ;

    /**
     * The Slim App instance.
     *
     * @var App
     * @access protected
     */
    protected App $app ;

    /**
     * Returns the base path of the application.
     *
     * This corresponds to the path configured in Slim and can be used
     * to generate URLs relative to the application root.
     *
     * @return string The application's base path (e.g., "/myapp")
     */
    public function getBasePath() :string
    {
        return $this->app->getBasePath() ;
    }

    /**
     * Generates a full URL for the application.
     *
     * The URL is constructed using the application's base URL, the base path,
     * the optional path, and query parameters.
     *
     * @param string $path   Optional relative path to append to the base URL.
     * @param array  $params Optional query parameters as key-value pairs.
     * @param bool   $useNow If true, timestamp-like parameters will be processed immediately.
     *
     * @return string The full URL including base URL, path, and query parameters.
     */
    public function getUrl( string $path = '' , array $params = [] , bool $useNow = false ) :string
    {
        return joinPaths( $this->baseUrl , $this->app->getBasePath() , $path ) . formatRequestArgs( $params , $useNow ) ;
    }

    /**
     * Initializes the internal `app` property.
     *
     * This method retrieves the Slim App instance with a specific priority:
     * 1. From the provided initialization array (e.g., ['app' => App instance]).
     * 2. If not in the array, from the DI container (using App::class or a custom key from init).
     *
     * It throws an exception if no valid App instance can be found.
     * This priority allows for easy overriding/mocking during tests.
     *
     * @param array $init Optional initialization array.
     * @param ContainerInterface|null $container Optional DI container.
     *
     * @return static Returns the current controller instance for method chaining.
     *
     * @throws NotFoundExceptionInterface If the container is used and the App class is not found.
     * @throws ContainerExceptionInterface If the container throws an internal error.
     * @throws RuntimeException If no valid App instance is provided or found.
     */
    public function initializeApp( array $init = [] , ?ContainerInterface $container = null  ):static
    {
        $app = $init[ ControllerParam::APP ] ?? App::class ;

        if( !$app instanceof App && is_string( $app ) && $container?->has( $app ) )
        {
            $app = $container->get( $app ) ;
        }

        if( !$app instanceof App )
        {
            throw new RuntimeException
            (
                'Could not initialize App. It was not provided in the init array (under the "'. ControllerParam::APP .'" key) and could not be found in the DI container.'
            ) ;
        }

        $this->app = $app ;

        return $this ;
    }
}