<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a numeric parameter from the request and clamps it within a given range.
 *
 * This helper calls {@see getParam()} and:
 * - Converts the value to int or float.
 * - Returns `$defaultValue` if the parameter is missing or not numeric.
 * - Clamps the value between `$min` and `$max`.
 * - Can throw {@see NotFoundException} if `$throwable` is true and parameter is missing.
 *
 * @param Request|null       $request      The PSR-7 request instance.
 * @param string             $name         Parameter name or dot-notated path.
 * @param int|float          $min          Minimum allowed value.
 * @param int|float          $max          Maximum allowed value.
 * @param int|float|null     $defaultValue Value returned if missing or invalid. Default null.
 * @param array              $args         Optional defaults passed to {@see getParam()}.
 * @param ?string            $strategy     Source to search: BOTH, QUERY, BODY. Default BOTH.
 * @param bool               $throwable    Whether to throw {@see NotFoundException} if missing. Default false.
 *
 * @return int|float|null The numeric value clamped to the range [$min, $max], or `$defaultValue`/null if missing or invalid.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @example
 * ```php
 * // Query: ?price=15
 * $price = getParamNumberRange($request, 'price', 0, 100, 10); // 15
 *
 * // Body: ['discount' => 150]
 * $discount = getParamNumberRange($request, 'discount', 0, 100, 0); // 100 (clamped)
 *
 * // Missing value
 * $tax = getParamNumberRange($request, 'tax', 0, 50, 5); // 5 (default)
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamNumberRange
(
    ?Request       $request ,
    string         $name ,
    int|float      $min ,
    int|float      $max ,
    null|int|float $defaultValue = null ,
    array          $args         = [] ,
    ?string        $strategy     = HttpParamStrategy::BOTH ,
    bool           $throwable    = false
)
:int|float|null
{
    $value = getParam( $request , $name , $args , $strategy , $throwable ) ;

    if ( !isset( $value ) || !is_numeric( $value ) )
    {
        return $defaultValue ;
    }

    $num = $value + 0 ; // cast to int or float

    if ( $num < $min )
    {
        return $min;
    }

    if ( $num > $max )
    {
        return $max ;
    }

    return $num ;
}