<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use org\schema\constants\Prop;

use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getQueryParam;
use function oihana\core\date\isDate;

/**
 * Prepares a date parameter of a controller request.
 *
 * This trait resolves a date value (formatted with the `Y-m-d` pattern by default, or
 * the {@see ControllerParam::DATE_FORMAT} argument) from the route arguments or the
 * matching controller property, falling back to the current day when the candidate is
 * not a valid date. The request query string may override it, and only a valid query
 * value is written back into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareDate
{
    /**
     * Prepares and returns a date string.
     *
     * The value is read from `$args[$name]`, then the `$name` property, then `$default`,
     * and is replaced by today's date when it does not match the resolved format. A valid
     * `$name` query parameter overrides the value and is stored in `$params`.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial date and a `dateFormat` entry.
     * @param array|null   $params  A reference to the parameter bag updated in place with the prepared date.
     * @param string|null  $default The fallback date used when neither the arguments nor the property provide a valid value.
     * @param string       $name    The name of the date parameter to read and store (defaults to {@see Prop::DATE}).
     *
     * @return string|null The resolved, format-validated date string.
     */
    protected function prepareDate
    (
        ?Request $request ,
         array   $args    = [] ,
        ?array   &$params = null ,
        ?string  $default = null ,
         string  $name    = Prop::DATE
    )
    :?string
    {
        $format = $args[ ControllerParam::DATE_FORMAT ] ?? 'Y-m-d' ;
        $value  = $args[ $name ] ?? $this->{ $name } ?? $default  ;
        $value  = isDate( $value , $format ) ? $value : date( $format ) ;

        $flag = false ;

        if( $request )
        {
            $queryParam = getQueryParam( $request , $name ) ;
            if( isDate( $queryParam , $format ) )
            {
                $value = $queryParam ;
                $flag = true ;
            }
        }

        if( isset( $value ) && is_array( $params ) && $flag )
        {
            $params[ $name ] = $value ;
        }

        return $value ;
    }
}