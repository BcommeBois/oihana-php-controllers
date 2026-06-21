<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves a parameter from the HTTP request and ensures it is a string.
 *
 * This helper calls {@see getParam()} and converts the returned value to a string if set.
 * - If the value is `null` or missing, the `$defaultValue` is returned.
 * - If `$throwable` is true, a {@see NotFoundException} may be thrown by `getParam()`.
 *
 * @param Request|null $request      The PSR-7 server request instance.
 * @param string       $name         The parameter name or dot-notated path.
 * @param array        $args         Optional default values passed to `getParam()`.
 * @param string|null  $defaultValue Value returned if the parameter is missing or null. Default is null.
 * @param ?string      $strategy     Which source to search: `HttpParamStrategy::BOTH|QUERY|BODY`. Default is BOTH.
 * @param bool         $throwable    Whether to throw a `NotFoundException` if parameter is missing. Default false.
 *
 * @return string|null The parameter value cast to string if present, otherwise `$defaultValue` or null.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @example
 * ```php
 * // Query string: ?name=Alice
 * $name = getParamString($request, 'name'); // "Alice"
 *
 * // Body: ['title' => 'Manager']
 * $title = getParamString($request, 'title', [], 'Default'); // "Manager"
 *
 * // Missing parameter, uses default
 * $nickname = getParamString($request, 'nickname', [], 'Guest'); // "Guest"
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamString
(
    ?Request $request ,
    string   $name    ,
    array    $args         = [] ,
    ?string  $defaultValue = null ,
    ?string  $strategy     = HttpParamStrategy::BOTH ,
    bool     $throwable    = false
)
:?string
{
    $value = getParam($request, $name, $args, $strategy, $throwable);
    return isset( $value ) ? (string) $value : $defaultValue ;
}