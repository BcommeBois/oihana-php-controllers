<?php

namespace oihana\controllers\helpers ;

use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\core\accessors\getKeyValue;
use function oihana\core\accessors\hasKeyValue;
use function oihana\core\accessors\setKeyValue;
use function oihana\core\objects\toAssociativeArray;

/**
 * Retrieves multiple parameters from the HTTP request body.
 *
 * This helper extracts one or more values from the parsed request body (`$request->getParsedBody()`),
 * supporting **dot notation** for nested structures (e.g. `'user.address.city'`).
 *
 * Each requested key from `$names` is resolved recursively via
 * {@see hasKeyValue()} and {@see getKeyValue()}, and reassembled into a new associative
 * array using {@see setKeyValue()}. The request body is first normalized into a pure associative
 * array using {@see toAssociativeArray()}, ensuring compatibility with both `array` and `stdClass` payloads.
 *
 * If the request is `null`, the function returns `null`.
 * If none of the requested keys exist, an empty array is returned.
 *
 * @param Request|null $request The PSR-7 server request instance. If `null`, no extraction is performed.
 * @param array        $names   A list of parameter names (keys or dot-notated paths) to extract.
 *
 * @return array An associative array of extracted values. Nested keys are preserved according to dot notation.
 *
 * @example
 * Retrieve multiple flat parameters:
 * ```php
 * // POST body: ['name' => 'Alice', 'age' => 30]
 * $params = getBodyParams($request, ['name', 'age']);
 * // ['name' => 'Alice', 'age' => 30]
 * ```
 *
 * Retrieve nested parameters with dot notation:
 * ```php
 * // POST body: ['user' => ['profile' => ['email' => 'a@b.c', 'active' => true]]]
 * $params = getBodyParams($request, ['user.profile.email', 'user.profile.active']);
 * // ['user' => ['profile' => ['email' => 'a@b.c', 'active' => true]]]
 * ```
 *
 * Handle missing keys or null request:
 * ```php
 * getBodyParams(null, ['foo', 'bar']);  // []
 * getBodyParams($request, ['unknown']); // []
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function getBodyParams( ?Request $request , array $names = [] ) :array
{
    if( $request )
    {
        $variables = [] ;
        $params = toAssociativeArray( $request->getParsedBody() ?? [] ) ;
        foreach( $names as $name )
        {
            if ( hasKeyValue( $params , $name ) )
            {
                $variables = setKeyValue( $variables , $name , getKeyValue( $params , $name ) ) ;
            }
        }
        return $variables ;
    }
    return []  ;
}