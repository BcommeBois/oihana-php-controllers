<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a parameter from the HTTP request and ensures it is a float number.
 *
 * This helper calls {@see getParam()} and converts the returned value to a float if set.
 * - If the value is `null` or missing, the `$defaultValue` is returned.
 * - If `$throwable` is true, a {@see NotFoundException} may be thrown by `getParam()`.
 *
 * @param Request|null $request      The PSR-7 server request instance.
 * @param string       $name         The parameter name or dot-notated path.
 * @param array        $args         Optional default values passed to `getParam()`.
 * @param float|null   $defaultValue Value returned if the parameter is missing or not set. Default is null.
 * @param ?string      $strategy     Which source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default is BOTH.
 * @param bool         $throwable    Whether to throw a `NotFoundException` if parameter is missing. Default false.
 *
 * @return float|null The parameter value cast to float if present, otherwise `$defaultValue` or null.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @example
 * ```php
 * // Query string: ?price=19.95
 * $price = getParamFloat($request, 'price', [], 0.0); // 19.95
 *
 * // Body: ['discount' => '5.5']
 * $discount = getParamFloat($request, 'discount', [], null); // 5.5
 *
 * // Missing parameter, uses default
 * $tax = getParamFloat($request, 'tax', [], 1.0); // 1.0
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamFloat
(
    ?Request $request ,
    string   $name    ,
    array    $args         = [] ,
    ?float   $defaultValue = null ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
:?float
{
    $value = getParam( $request , $name , $args , $strategy , $throwable ) ;
    return isset( $value ) && is_numeric( $value ) ? (float) $value : $defaultValue ;
}