<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Prepares the `hasTotal` flag of a controller request.
 *
 * This boolean flag controls whether a list endpoint also computes and returns the total
 * number of matching elements. The trait exposes a `$hasTotal` property (with an
 * {@see initializeHasTotal()} initializer) and delegates the request-driven resolution to
 * {@see PrepareBoolean::prepareBoolean()} bound to the {@see ControllerParam::HAS_TOTAL}
 * parameter.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareHasTotal
{
    use PrepareBoolean ;

    /**
     * Indicates if the list method return the total number of elements.
     * @var bool
     */
    public bool $hasTotal = true ;

    /**
     * Initialize the hasTotal property with an associative array definition.
     * @param array $init The configuration array, optionally carrying a {@see ControllerParam::HAS_TOTAL} entry.
     * @return static The current instance for chaining.
     */
    public function initializeHasTotal( array $init = [] ):static
    {
        $this->hasTotal = $init[ ControllerParam::HAS_TOTAL ] ?? $this->hasTotal ;
        return $this ;
    }

    /**
     * Prepare and returns the 'hasTotal' value.
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial `hasTotal` value.
     * @param array|null   $params  A reference to the parameter bag updated in place with the prepared value.
     * @return bool The resolved `hasTotal` flag.
     */
    public function prepareHasTotal( ?Request $request , array $args = [] , ?array &$params = null ) :bool
    {
        return $this->prepareBoolean( $request , $args , $params , ControllerParam::HAS_TOTAL ) ;
    }
}