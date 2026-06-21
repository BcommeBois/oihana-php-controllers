<?php

namespace oihana\controllers\helpers ;

use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\core\accessors\getKeyValue;
use function oihana\core\accessors\hasKeyValue;
use function oihana\core\objects\toAssociativeArray;

/**
 * Retrieves a single parameter from the HTTP request body.
 *
 * This helper extracts a value from the parsed request body (`$request->getParsedBody()`),
 * supporting **dot notation** for nested structures (e.g. `'user.address.city'`).
 *
 * The body is internally normalized into a fully associative array using
 * {@see toAssociativeArray()}, ensuring compatibility with both array and stdClass-based JSON payloads.
 *
 * It internally uses {@see hasKeyValue()} and {@see getKeyValue()} from the `oihana\core\accessors` namespace.
 *
 * If the request is `null`, or if the specified parameter does not exist, the function returns `null`.
 *
 * @param Request|null $request The PSR-7 server request instance.
 * @param string $name The parameter name or nested key path (e.g. `'geo.latitude'`).
 *
 * @return mixed The parameter value if found, or `null` otherwise.
 *
 * @example
 * Retrieve a flat parameter:
 * ```php
 * // POST body: ['name' => 'Alice']
 * echo getBodyParam($request, 'name'); // 'Alice'
 * ```
 *
 * Retrieve a nested parameter using dot notation:
 * ```php
 * // POST body: ['geo' => ['latitude' => 42.5, 'longitude' => 1.5]]
 * echo getBodyParam($request, 'geo.latitude'); // '42.5'
 * ```
 *
 * Handle missing keys or null request:
 * ```php
 * echo getBodyParam(null, 'foo');            // null
 * echo getBodyParam($request, 'not.exists'); // null
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getBodyParam( ?Request $request , string $name ) :mixed
{
    if ( $request )
    {
        $params = toAssociativeArray( $request->getParsedBody() ?? [] ) ;
        if ( hasKeyValue( $params , $name ) )
        {
            return getKeyValue( $params , $name ) ;
        }
    }
    return null ;
}