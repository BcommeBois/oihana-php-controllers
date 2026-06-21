<?php

namespace oihana\controllers\traits ;

use RuntimeException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;

/**
 * Trait providing helpers to manage the application instance and generate URLs.
 *
 * This trait allows controllers to access the Slim App instance, retrieve
 * the application's base path, and build URLs with optional query parameters.
 *
 * Note: You can define a `baseUrl` in the DI container to use it consistently
 * across all controllers.
 */
trait ApiTrait
{
    /**
     * The default api settings.
     */
    protected array $api = [] ;

    /**
     * Initializes the internal `api` settings.
     *
     * By default, this method search in the DI container a ControllerParam::API definition to initialize the "api" property.
     *
     * @param array $init Optional initialization array (e.g., ['api' => [ ... ] ] ).
     * @param ContainerInterface|null $container Optional DI container for retrieving the 'api' array representation.
     *
     * @return static Returns the current controller instance for method chaining.
     *
     * @throws NotFoundExceptionInterface If the container is used and the 'api' definition is not found in the DI container.
     * @throws ContainerExceptionInterface If the container throws an internal error.
     * @throws RuntimeException If no valid App instance is provided or found.
     */
    public function initializeApi( array $init = [] , ?ContainerInterface $container = null  ):static
    {
        $api = $init[ ControllerParam::API ] ?? [];

        if( $container instanceof ContainerInterface && $container->has( ControllerParam::API ) )
        {
            $api = $container->get( ControllerParam::API ) ;
        }

        $this->api = is_array( $api ) ? $api : [] ;

        return $this ;
    }
}