<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a parameter from the HTTP request and converts it to a boolean.
 *
 * This helper calls {@see getParam()} and converts the returned value to a PHP boolean
 * according to standard boolean representations:
 * - `true`/`false` (boolean)
 * - `"true"` / `"false"` (string)
 * - `"1"` / `"0"` (string)
 * - `"yes"` / `"no"` (string)
 * - `"on"` / `"off"` (string)
 * - `1` / `0` (integer)
 *
 * If the value cannot be interpreted as a boolean, the provided `$defaultValue` is returned.
 * If `$request` is null or the parameter is missing, `$defaultValue` is returned unless
 * `$throwable` is set to true, in which case a {@see NotFoundException} is thrown.
 *
 * @param Request|null $request      The PSR-7 server request instance.
 * @param string       $name         The parameter name or dot-notated path.
 * @param array        $args         Optional defaults passed to {@see getParam()}.
 * @param bool|null    $defaultValue Value returned if the parameter is missing or not a boolean. Default is null.
 * @param ?string      $strategy     Source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default is BOTH.
 * @param bool         $throwable    Whether to throw {@see NotFoundException} if parameter is missing. Default false.
 *
 * @return bool|null The boolean value of the parameter, or `$defaultValue`/null if not found or unrecognized.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @example
 * ```php
 * // Query string: ?active=true
 * $active = getParamBool($request, 'active', [], false);
 *
 * // Body parameters: ['enabled' => '0']
 * $enabled = getParamBool($request, 'enabled', [], true);
 *
 * // Missing parameter with default fallback
 * $flag = getParamBool($request, 'flag', [], true);
 *
 * // Throw exception if parameter missing
 * $required = getParamBool($request, 'required', [], null, HttpParamStrategy::BOTH, true);
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamBool
(
    ?Request $request ,
    string   $name    ,
    array    $args         = [] ,
    ?bool    $defaultValue = null ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
:?bool
{
    if ( $request === null )
    {
        return $defaultValue ;
    }

    $value = getParam( $request , $name , $args , $strategy , $throwable ) ;

    if ( $value === null )
    {
        return $defaultValue;
    }

    return filter_var( $value , FILTER_VALIDATE_BOOLEAN , FILTER_NULL_ON_FAILURE ) ?? $defaultValue ;
}