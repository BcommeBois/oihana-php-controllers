<?php

namespace oihana\controllers\traits ;

use xyz\oihana\schema\Pagination;

use ReflectionException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;

/**
 * Trait providing helpers to manage the application/api pagination settings.
 *
 * This trait allows controllers to access the Slim App instance, retrieve
 * the application's pagination definition.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PaginationTrait
{
    /**
     * The pagination definition.
     * @var ?Pagination
     */
    public ?Pagination $pagination = null ;

    /**
     * Initializes the `pagination` property.
     *
     * This method retrieves the default pagination settings for the application,
     * either from the provided initialization array or from the dependency injection container.
     *
     * @param array                   $init      Optional initialization array (e.g., ['pagination' => Pagination instance]).
     * @param ContainerInterface|null $container Optional DI container used to retrieve the default pagination definition.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws NotFoundExceptionInterface If no entry was found in the container for the given identifier.
     * @throws ReflectionException If a class or method cannot be reflected.
     */
    public function initializePagination( array $init = [] , ?ContainerInterface $container = null ):static
    {
        $pagination = $init[ ControllerParam::PAGINATION ] ?? null;

        if( $pagination == null && $container instanceof ContainerInterface && $container->has( ControllerParam::PAGINATION ) )
        {
            $pagination = $container->get( ControllerParam::PAGINATION ) ;
        }

        if( is_array( $pagination ) )
        {
            $pagination = new Pagination( $pagination ) ;
        }

        $this->pagination = $pagination instanceof Pagination ? $pagination : null ;

        return $this ;
    }
}