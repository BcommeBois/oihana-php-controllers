<?php

namespace oihana\controllers\helpers ;

use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\core\accessors\getKeyValue;
use function oihana\core\accessors\hasKeyValue;

/**
 * Retrieves a single parameter from the HTTP request query string.
 *
 * This helper extracts a value from the query parameters (`$request->getQueryParams()`),
 * supporting **dot notation** for nested structures (e.g. `'filter.page'`).
 *
 * It internally uses {@see hasKeyValue()} and {@see getKeyValue()} from the
 * `oihana\core\accessors` namespace.
 *
 * If the request is `null`, or if the specified parameter does not exist, the function returns `null`.
 *
 * @param Request|null $request The PSR-7 server request instance.
 * If `null`, no extraction is performed.
 * @param string $name The query parameter name or nested key path (e.g. `'filter.page'`).
 *
 * @return mixed The parameter value if found, or `null` otherwise.
 *
 * @example
 * Retrieve a flat parameter:
 * ```php
 * // URL: /api/users?name=Alice&age=30
 * echo getQueryParam($request, 'name'); // 'Alice'
 * echo getQueryParam($request, 'age');  // '30'
 * ```
 *
 * Retrieve a nested parameter using dot notation:
 * ```php
 * // URL: /api/users?filter[page]=2&filter[limit]=10
 * echo getQueryParam($request, 'filter.page');  // '2'
 * echo getQueryParam($request, 'filter.limit'); // '10'
 * ```
 *
 * Handle missing keys or null request:
 * ```php
 * getQueryParam(null, 'foo');            // null
 * getQueryParam($request, 'not.exists'); // null
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getQueryParam( ?Request $request , string $name ) :mixed
{
    if( $request )
    {
        $params = $request->getQueryParams() ;
        if ( hasKeyValue( $params , $name ) )
        {
            return getKeyValue( $params , $name ) ;
        }
    }
    return null  ;
}