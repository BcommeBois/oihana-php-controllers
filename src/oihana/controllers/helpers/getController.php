<?php

namespace oihana\controllers\helpers ;

use oihana\controllers\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;

/**
 * Retrieves a controller instance from a PSR-11 container if available.
 *
 * This function attempts to fetch a controller by its identifier (`$id`) from the given container.
 * If the container is provided and contains the specified entry, it is resolved and returned
 * if it is an instance of {@see Controller}. Otherwise, the optional `$default` controller is returned.
 *
 * @param array|string|null|Controller $definition The controller definition within the container.
 * @param ContainerInterface|null      $container  The PSR-11 container to fetch the controller from (optional).
 * @param Controller|null              $default    A fallback controller to return if the container does not provide one (optional).
 *
 * @return Controller|null The resolved controller instance or the provided default value, or `null` if none found.
 *
 * @throws ContainerExceptionInterface If an error occurs while retrieving the controller from the container.
 * @throws NotFoundExceptionInterface  If the controller identifier does not exist in the container.
 */
function getController
(
    array|string|null|Controller $definition = null ,
    ?ContainerInterface          $container  = null ,
    ?Controller                  $default    = null
)
:?Controller
{
    if( $definition instanceof Controller )
    {
        return $definition ;
    }

    if( is_array( $definition ) )
    {
        $definition = $definition[ ControllerParam::CONTROLLER ] ?? null ;
    }

    if( is_string( $definition ) && $container?->has( $definition ) )
    {
        $definition = $container->get( $definition ) ;
    }

    return $definition instanceof Controller ? $definition : $default ;
}