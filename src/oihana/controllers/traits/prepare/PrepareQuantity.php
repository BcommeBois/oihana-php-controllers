<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Prepares the `quantity` parameter of a controller request.
 *
 * This trait delegates to {@see PrepareInt::prepareInt()} bound to the
 * {@see ControllerParam::QUANTITY} parameter, so the request query string drives an
 * integer-validated value and, when present, the result is written back into the
 * parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareQuantity
{
    use PrepareInt ;

    /**
     * Prepares and returns the `quantity` value.
     *
     * @param Request|null $request      The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args         The route/controller arguments that may carry an initial `quantity` value.
     * @param array|null   $params       A reference to the parameter bag updated in place with the prepared value.
     * @param int|null     $defaultValue The fallback value used when no valid integer is resolved.
     * @return int|null The resolved quantity, or null when none is available.
     */
    protected function prepareQuantity( ?Request $request , array $args = [] , ?array &$params = null , ?int $defaultValue = null ) :?int
    {
        return $this->prepareInt( $request , $args , $params , $defaultValue , ControllerParam::QUANTITY ) ;
    }
}