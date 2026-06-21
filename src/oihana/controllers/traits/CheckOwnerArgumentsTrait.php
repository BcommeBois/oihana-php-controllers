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
 *
 * @package oihana\models\traits
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
     * @param array $args array<string, mixed> $args Arguments to validate
     *
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     * @throws Error404
     * @throws Error500
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
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
     * @param array<string, mixed> $init Initialization array
     *
     * @return static
     */
    public function initializeOwner( array $init = [] ):static
    {
        $this->owner = $init[ ControllerParam::OWNER ] ?? $this->owner ;
        return $this;
    }
}