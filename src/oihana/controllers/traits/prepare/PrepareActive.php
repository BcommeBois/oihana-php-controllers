<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the `active` flag of a controller request.
 *
 * This trait reads the {@see ControllerParam::ACTIVE} value and encapsulates the
 * convention used to disable it: the flag stays at its default (or the value carried
 * by the route arguments) unless the request supplies a query parameter whose value
 * is one of `0`, `false` or `FALSE`, in which case the flag becomes `false`.
 *
 * Only the query string is inspected (the request body is ignored).
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareActive
{
    /**
     * Prepares and returns the `active` flag.
     *
     * The value resolves to the {@see ControllerParam::ACTIVE} entry of `$args` (or
     * `$defaultValue` when absent), and is forced to `false` only when the request
     * carries an `active` query parameter equal to `0`, `false` or `FALSE`.
     *
     * @param Request|null $request      The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args         The route/controller arguments that may carry an initial `active` value.
     * @param bool         $defaultValue The value used when neither the arguments nor the request define the flag.
     *
     * @return bool|null The resolved `active` flag.
     */
    protected function prepareActive( ?Request $request , array $args = [] , bool $defaultValue = true ) :bool|null
    {
        $active = $args[ ControllerParam::ACTIVE ] ?? $defaultValue ;
        if( isset( $request ) )
        {
            $param = getQueryParam( $request , ControllerParam::ACTIVE ) ; // query param only (not body)
            if( $param == '0' || $param == 'false' || $param == 'FALSE' )
            {
                $active = false ;
            }
        }
        return $active ;
    }
}