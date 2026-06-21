<?php

namespace oihana\controllers\traits\prepare;

use oihana\enums\Boolean;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares a generic boolean parameter of a controller request.
 *
 * This trait is the shared building block reused by the dedicated boolean traits
 * (`bench`, `margin`, `mock`, `hasTotal`, …). It resolves the value from the route
 * arguments or the matching controller property, then lets the request query string
 * override it through {@see filter_var()} with `FILTER_VALIDATE_BOOLEAN`. When the
 * request provides a value, the normalized boolean is written back into the parameter
 * bag as the {@see Boolean::TRUE} / {@see Boolean::FALSE} string convention.
 *
 * Only the query string is inspected (the request body is ignored).
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareBoolean
{
    /**
     * Prepares and returns a boolean value for the given parameter name.
     *
     * The value defaults to `$args[$name]`, then to the `$name` property of the current
     * instance, then to `false`. When the request carries a `$name` query parameter, it
     * is parsed with `FILTER_VALIDATE_BOOLEAN` and, on success, both the returned value
     * and the {@see Boolean::TRUE}/{@see Boolean::FALSE} entry stored in `$params` are
     * updated.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial value for `$name`.
     * @param array|null   $params  A reference to the parameter bag updated in place with the normalized boolean string.
     * @param string|null  $name    The name of the boolean parameter to read and store.
     *
     * @return bool|null The resolved boolean value.
     */
    protected function prepareBoolean( ?Request $request , array $args = [] , ?array &$params = null , ?string $name = null ) :?bool
    {
        $property = $args[ $name ] ?? $this?->{ $name } ?? false ;

        $flag = false ;
        if( isset( $request ) )
        {
            $value = getQueryParam( $request , $name ) ; // query param only (not body).
            if( isset( $value ) )
            {
                $flag = true ;
                $property = filter_var( $value , FILTER_VALIDATE_BOOLEAN , FILTER_NULL_ON_FAILURE ) ?? $property ;
            }
        }

        if( $flag )
        {
            $params[ $name ] = $property ? Boolean::TRUE : Boolean::FALSE ;
        }

        return $property ;
    }
}