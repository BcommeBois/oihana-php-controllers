<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Retrieves an i18n parameter from the HTTP request, optionally filtered by allowed languages.
 *
 * This helper retrieves a parameter `$name` from the PSR-7 `$request`, supporting query string,
 * body, or both (`HttpParamStrategy`). The parameter can be an array or object of translations
 * (e.g., `['fr' => 'Bonjour', 'en' => 'Hello']`). It then filters the values according to `$languages`
 * and optionally applies a `$sanitize` callback on each value.
 *
 * Nested keys in the request are supported via dot notation (e.g., 'user.profile.email').
 *
 * If the parameter is not found:
 * - Returns `$default[$name]` if present.
 * - Returns `null` if no default is provided.
 * - Throws `NotFoundException` if `$throwable` is true.
 *
 * @param Request|null       $request    The PSR-7 server request instance.
 * @param string             $name       The parameter name or dot-notated path.
 * @param array              $default    Optional default values as an associative array.
 * @param array<string>|null $languages  Optional array of languages to filter the i18n definitions. If null, no filtering is applied.
 * @param callable|null      $sanitize   Optional callback to transform or sanitize each value. Signature: `fn(string|null $value, string $lang): string|null`
 * @param ?string            $strategy   One of `HttpParamStrategy::QUERY|BODY|BOTH`. Default: BOTH.
 * @param bool               $throwable  Whether to throw a `NotFoundException` if parameter is missing. Default: false.
 *
 * @return ?array The i18n value if found, otherwise the default value or null.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 * @example
 * ```php
 * $request = ...; // PSR-7 request with body/query
 *
 * // Retrieve translations for 'description' filtered to 'fr' and 'en'
 * $translations = getParamI18n
 * (
 *      $request ,
 *      'description' ,
 *      ['description' => ['fr' => 'Default FR', 'en' => 'Default EN']],
 *      ['fr', 'en'],
 *      fn($v,$lang) => is_string($v) ? strtoupper($v) : $v
 * );
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getParamI18n
(
    ?Request  $request   ,
    string    $name      ,
    array     $default   = [] ,
    ?array    $languages = null ,
    ?callable $sanitize  = null ,
    ?string   $strategy  = HttpParamStrategy::BOTH ,
    bool      $throwable = false
)
:?array
{
    return filterLanguages
    (
        getParam( $request , $name , $default , $strategy , $throwable ) ,
        $languages ,
        $sanitize
    ) ;
}