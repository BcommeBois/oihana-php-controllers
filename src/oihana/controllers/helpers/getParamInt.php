<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a parameter from the HTTP request and ensures it is a int number.
 *
 * This helper calls {@see getParam()} and converts the returned value to a int if set.
 * - If the value is `null` or missing, the `$defaultValue` is returned.
 * - If `$throwable` is true, a {@see NotFoundException} may be thrown by `getParam()`.
 *
 * @param Request|null $request      The PSR-7 server request instance.
 * @param string       $name         The parameter name or dot-notated path.
 * @param array        $args         Optional default values passed to `getParam()`.
 * @param int|null     $defaultValue Value returned if the parameter is missing or not set. Default is null.
 * @param ?string      $strategy     Which source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default is BOTH.
 * @param bool         $throwable    Whether to throw a `NotFoundException` if parameter is missing. Default false.
 *
 * @return int|null The parameter value cast to int if present, otherwise `$defaultValue` or null.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @example
 * ```php
 * // Query string: ?age=19
 * $price = getParamInt($request, 'age', [], 0); // 19
 *
 * // Body: ['age' => '5']
 * $discount = getParamInt($request, 'age', [], null); // 5
 *
 * // Missing parameter, uses default
 * $tax = getParamInt($request, 'age', [], 1); // 1
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamInt
(
    ?Request $request ,
    string   $name    ,
    array    $args         = [] ,
    ?int     $defaultValue = null ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
:?int
{
    $value = getParam( $request , $name , $args , $strategy , $throwable ) ;
    return isset( $value ) && is_numeric( $value ) ? (int) $value : $defaultValue ;
}