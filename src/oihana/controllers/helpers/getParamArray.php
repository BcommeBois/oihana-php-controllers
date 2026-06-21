<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a parameter from the HTTP request and ensures it is an array.
 *
 * This helper calls {@see getParam()} and checks the returned value:
 * - If the value is an array, it is returned.
 * - Otherwise, the `$defaultValue` is returned.
 * - If `$throwable` is true, a `NotFoundException` may be thrown by `getParam()`.
 *
 * @param Request|null $request      The PSR-7 server request instance.
 * @param string       $name         The parameter name or dot-notated path.
 * @param array        $args         Optional default values passed to `getParam()`.
 * @param array|null   $defaultValue Value returned if the parameter is missing or not an array. Default is null.
 * @param ?string      $strategy     Which source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default is BOTH.
 * @param bool         $throwable    Whether to throw a `NotFoundException` if parameter is missing. Default false.
 *
 * @return array|null The parameter value if it is an array, otherwise `$defaultValue` or null.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @example
 * ```php
 * // Query string: ?filters[status]=active&filters[roles][]=admin
 * $filters = getParamArray($request, 'filters', [], ['status' => 'all']);
 * ```
 *
 * @example
 * ```php
 * // Body: ['user' => ['roles' => ['editor', 'admin']]]
 * $roles = getParamArray($request, 'user.roles', [], []);
 * ```
 *
 * @example
 * ```php
 * // Non-array value, returns default
 * $tags = getParamArray($request, 'tags', [], ['default']);
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamArray
(
    ?Request $request ,
    string   $name    ,
    array    $args         = [] ,
    ?array   $defaultValue = null ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
:?array
{
    $value = getParam( $request , $name , $args , $strategy , $throwable ) ;
    return is_array( $value ) ? $value : $defaultValue ;
}