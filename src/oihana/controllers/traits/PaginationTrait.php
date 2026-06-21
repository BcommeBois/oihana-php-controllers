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
     * @param array $init Optional initialization array (e.g., ['pagination' => Pagination instance]).
     * @param ContainerInterface|null $container Optional DI container for retrieving the App instance.
     *
     * @return static Returns the current controller instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container throws an internal error.
     * @throws NotFoundExceptionInterface If the container is used and the App class is not found.
     * @throws ReflectionException
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