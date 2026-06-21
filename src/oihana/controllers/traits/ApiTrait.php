<?php

namespace oihana\controllers\traits ;

use RuntimeException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;

/**
 * Provides a controller-level `api` settings array, hydrated either from an
 * initialization array or from a PSR-11 container entry.
 *
 * The settings are exposed through the protected `$api` property and populated
 * by {@see self::initializeApi()}, so API-related configuration (endpoints,
 * keys, feature flags, etc.) can be centralized in the DI container and shared
 * consistently across all controllers.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ApiTrait
{
    /**
     * The default api settings.
     *
     * @var array
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