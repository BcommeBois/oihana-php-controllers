<?php

namespace oihana\controllers\traits;

use DI\DependencyException;
use DI\NotFoundException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\exceptions\http\Error404;
use oihana\exceptions\http\Error500;
use oihana\models\enums\ModelParam;
use oihana\models\interfaces\ExistModel;
use oihana\models\traits\DocumentsTrait;

use function oihana\models\helpers\getDocumentsModel;

/**
 * Utilities to validate "owner" arguments against Documents models.
 *
 * This is mainly used to ensure that arguments passed to `get()`, `list()`, `count()` or `exist()` methods
 * actually correspond to existing document records.
 *
 * ```php
 * $controller = new class
 * {
 *     use \oihana\controllers\traits\CheckOwnerArgumentsTrait;
 * };
 *
 * // Initialize owner definitions
 * $controller->initializeOwner
 * ([
 *     'owner' =>
 *     [
 *         'userId' => $userModel,
 *         'accountId' => $accountModel,
 *     ]
 * ]);
 *
 * // Validate arguments (throws Error404 if a value is not found)
 * $controller->checkOwnerArguments
 * ([
 *     'userId'    => 123,
 *     'accountId' => 456,
 * ]);
 *
 * // It's safe to call with missing args: they will be ignored
 * $controller->checkOwnerArguments([ 'userId' => 123 ]);
 * ```
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait CheckOwnerArgumentsTrait
{
    use DocumentsTrait ;

    /**
     * @var array<string, mixed>|null Collection of owner's arguments to check
     */
    public ?array $owner = null ;

    /**
     * Check all 'owner' arguments against their Documents model.
     *
     * Example:
     * ```php
     * $controller->owner =
     * [
     *     'userId' => $userModel,
     * ];
     * $controller->checkOwnerArguments([ 'userId' => 1 ]);
     * ```
     *
     * @param array $args array<string, mixed> $args Arguments to validate.
     *
     * @return void
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws DependencyException If the dependency cannot be resolved by the container.
     * @throws Error404 If one of the checked arguments does not match an existing document record.
     * @throws Error500 If an argument is associated with a null or invalid Documents model reference.
     * @throws NotFoundException If the requested entry is not found in the container.
     * @throws NotFoundExceptionInterface If no entry was found in the container for the given identifier.
     */
    public function checkOwnerArguments( array $args = [] ) :void
    {
        if ( empty( $this->owner ) )
        {
            return ;
        }

        foreach( $this->owner as $arg => $documents )
        {
            if ( !array_key_exists( $arg , $args ) )
            {
                continue;
            }

            $documents = getDocumentsModel( $documents , $this->container ) ;

            if( $documents instanceof ExistModel )
            {
                if( !$documents->exist( [ ModelParam::VALUE => $args[ $arg ] ] ) )
                {
                    throw new Error404( sprintf( 'The %s argument is not found.' , $arg ) ) ;
                }
            }
            else
            {
                throw new Error500
                (
                    sprintf
                    (
                        "The %s argument can't be checked with a null or bad Documents model reference." ,
                        $arg
                    )
                ) ;
            }
        }
    }

    /**
     * Initialize the owner definition from an array.
     *
     * Example:
     * ```php
     * $controller->initializeOwner([ 'owner' => [ 'userId' => $userModel ] ]);
     * ```
     *
     * @param array<string, mixed> $init Initialization array, the owner definition is read from the {@see ControllerParam::OWNER} key.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializeOwner( array $init = [] ):static
    {
        $this->owner = $init[ ControllerParam::OWNER ] ?? $this->owner ;
        return $this;
    }
}