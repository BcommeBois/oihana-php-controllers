<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\core\accessors\getKeyValue;
use function oihana\core\accessors\hasKeyValue;
use function oihana\core\objects\toAssociativeArray;

/**
 * Retrieves a parameter from the HTTP request, supporting query string, body, or both.
 *
 * This helper searches for the requested parameter `$name` in the request according to the
 * specified `$strategy`:
 * - `HttpParamStrategy::QUERY`  → only query string parameters.
 * - `HttpParamStrategy::BODY`   → only parsed body parameters.
 * - `HttpParamStrategy::BOTH`   → query string first, then body.
 *
 * Nested keys are supported via dot notation (e.g., `'user.profile.email'`).
 * Body parameters are normalized to an associative array using {@see toAssociativeArray()}.
 *
 * If the parameter is not found:
 * - Returns the corresponding value in `$default[$name]` if present.
 * - Returns `null` if no default is provided.
 * - Throws `DI\NotFoundException` if `$throwable` is true.
 *
 * @param Request|null $request    The PSR-7 server request instance.
 * @param string       $name       The parameter name or dot-notated path.
 * @param array        $default    Optional default values as an associative array.
 * @param ?string      $strategy   One of `HttpParamStrategy::QUERY|BODY|BOTH`. Default: BOTH.
 * @param bool         $throwable  Whether to throw a `NotFoundException` if parameter is missing. Default: false.
 *
 * @return mixed The parameter value if found, otherwise the default value or null.
 *
 * @throws NotFoundException If `$throwable` is true and the parameter is not found.
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @example
 * ```php
 * // Query parameter only
 * $request = ...; // ?name=Alice
 * getParam($request, 'name', [], HttpParamStrategy::QUERY);
 *
 * // Body parameter only
 * $request = ...; // ['user' => ['email' => 'a@b.c']]
 * getParam($request, 'user.email', [], HttpParamStrategy::BODY);
 *
 * // Both sources, with fallback
 * getParam($request, 'foo', ['foo' => 'default'], HttpParamStrategy::BOTH);
 *
 * // Throw exception if missing
 * getParam($request, 'bar', [], HttpParamStrategy::BOTH, true);
 * ```
 */
function getParam
(
    ?Request $request   ,
    string   $name      ,
    array    $default   = [] ,
    ?string  $strategy  = HttpParamStrategy::BOTH ,
    bool     $throwable = false
)
:mixed
{
    if( $request )
    {
        $strategy = HttpParamStrategy::includes( $strategy , true ) ? $strategy : HttpParamStrategy::BOTH ;

        if( $strategy == HttpParamStrategy::BOTH || $strategy == HttpParamStrategy::QUERY )
        {
            $params = $request->getQueryParams() ;
            if ( hasKeyValue( $params , $name ) )
            {
                return getKeyValue( $params , $name ) ;
            }
        }

        if( $strategy == HttpParamStrategy::BOTH || $strategy == HttpParamStrategy::BODY )
        {
            $params = toAssociativeArray( $request->getParsedBody() ?? [] ) ;
            if ( hasKeyValue( $params , $name ) )
            {
                return getKeyValue($params, $name);
            }
        }
    }

    if( $throwable )
    {
        throw new NotFoundException( sprintf( 'The parameter "%s" was not found.' , $name ) ) ;
    }

    return $default[ $name ] ?? null  ;
}