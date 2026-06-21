<?php

namespace oihana\controllers\traits\prepare;

use oihana\enums\FilterOption;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares a generic integer parameter of a controller request.
 *
 * This trait is the shared building block reused by the dedicated integer traits (such as
 * `quantity`). It resolves the value from the route arguments or the matching controller
 * property, then lets the request query string override it through {@see filter_var()}
 * with `FILTER_VALIDATE_INT` (honoring an optional {@see FilterOption::OPTIONS} range).
 * Non-integer results fall back to `$defaultValue`, and a request-provided value is
 * recorded in the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareInt
{
    /**
     * Prepares and returns an integer value for the given parameter name.
     *
     * @param Request|null $request      The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args         The route/controller arguments that may carry an initial value and validation options.
     * @param array|null   $params       A reference to the parameter bag updated in place with the prepared integer.
     * @param int|null     $defaultValue The fallback value used when no valid integer is resolved.
     * @param string|null  $name         The name of the integer parameter to read and store.
     * @return int|null The resolved integer value, or null when none is available.
     */
    protected function prepareInt( ?Request $request , array $args = [] , ?array &$params = null , ?int $defaultValue = null , ?string $name = null ) :?int
    {
        $value   = $args[ $name ] ?? $this->{ $name } ?? $defaultValue ;
        $options = $args[ FilterOption::OPTIONS ] ?? null ;
        $flag    = false ;

        if( isset( $request ) )
        {
            $param = getQueryParam( $request , $name ) ;
            if( isset( $param ) )
            {
                $flag = true ;
                $value = filter_var( $param , FILTER_VALIDATE_INT , [ FilterOption::OPTIONS => $options ] ) ;
            }
        }

        if( !is_int( $value ) )
        {
            $value = $defaultValue ;
        }

        if( $flag )
        {
            $params[ $name ] = $value ;
        }

        return $value ;
    }
}