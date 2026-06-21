<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves an integer parameter from the request and clamps it within a given range.
 *
 * Wrapper around {@see getParamNumberRange()} that ensures integer return type.
 *
 * @param Request|null $request      The PSR-7 server request instance. Can be null.
 * @param string       $name         The parameter name or dot-notated path.
 * @param int          $min          Minimum allowed integer value.
 * @param int          $max          Maximum allowed integer value.
 * @param int|null     $defaultValue Value returned if the parameter is missing or not numeric. Default null.
 * @param array        $args         Optional defaults passed to {@see getParam()}.
 * @param ?string      $strategy     Which source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default BOTH.
 * @param bool         $throwable    Whether to throw {@see NotFoundException} if the parameter is missing. Default false.
 *
 * @return int|null The integer value clamped to [$min, $max], or `$defaultValue`/null if missing or invalid.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found in the request.
 *
 * @example
 * ```php
 * $quantity = getParamIntRange($request, 'quantity', 1, 10, 5);
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamIntRange
(
    ?Request $request,
    string   $name,
    int      $min,
    int      $max,
    ?int     $defaultValue = null ,
    array    $args         = [] ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
: ?int
{
    $value = getParamNumberRange($request, $name, $min, $max, $defaultValue, $args, $strategy, $throwable);
    return isset($value) ? (int) $value : null;
}