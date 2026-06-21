<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a float parameter from the request and clamps it within a given range.
 *
 * Wrapper around {@see getParamNumberRange()} that ensures float return type.
 *
 * @param Request|null $request      The PSR-7 server request instance. Can be null.
 * @param string       $name         The parameter name or dot-notated path.
 * @param float        $min          Minimum allowed float value.
 * @param float        $max          Maximum allowed float value.
 * @param float|null   $defaultValue Value returned if the parameter is missing or not numeric. Default null.
 * @param array        $args         Optional defaults passed to {@see getParam()}.
 * @param ?string      $strategy     Which source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default BOTH.
 * @param bool         $throwable    Whether to throw {@see NotFoundException} if the parameter is missing. Default false.
 *
 * @return float|null The float value clamped to [$min, $max], or `$defaultValue`/null if missing or invalid.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found in the request.
 *
 * @return float|null
 *
 * @throws NotFoundException
 *
 * @example
 * ```php
 * $discount = getParamFloatRange($request, 'discount', 0.0, 100.0, 0.0);
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamFloatRange
(
    ?Request $request,
    string   $name,
    float    $min,
    float    $max,
    ?float   $defaultValue = null ,
    array    $args         = [] ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
: ?float
{
    $value = getParamNumberRange( $request , $name , $min , $max , $defaultValue , $args , $strategy , $throwable ) ;
    return isset( $value ) ? (float) $value : null ;
}