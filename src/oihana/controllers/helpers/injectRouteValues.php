<?php

namespace oihana\controllers\helpers ;

use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\enums\Char;

use function oihana\core\accessors\setKeyValue;
use function oihana\core\objects\toAssociativeArray;

/**
 * Returns the request whose parsed body carries the given route placeholders,
 * forced over whatever the caller supplied.
 *
 * A sub-resource route already names one of the values the payload needs:
 * `/owners/{ownerId}/items` says who the owner is, so the body should neither
 * have to repeat it nor be able to contradict it. This helper rewrites the
 * request so the designated body fields carry the route values.
 *
 * Body fields support **dot notation** (e.g. `'owner.id'`), like the rest of the
 * helper family: the value is written at the designated depth through
 * {@see setKeyValue()}, the missing levels being created and the sibling keys
 * left alone.
 *
 * The body is normalized with {@see toAssociativeArray()}, so both array and
 * stdClass-based JSON payloads are accepted — note that the rewritten body is
 * always an associative array. Route values are cast to string, a placeholder
 * always being text in the URL.
 *
 * Only a non-empty string or an int is injected: anything else — a missing, empty
 * or non-scalar value, or an empty target field — is skipped, the route saying
 * nothing about that field, so the caller's value stands. When no binding applies
 * at all, the request is returned untouched — no clone.
 *
 * @param Request|null         $request  The PSR-7 server request instance.
 * @param array<string,mixed>  $args     The route placeholders, as handed to the controller verb.
 * @param array<string,string> $bindings The placeholder name => body field map.
 *
 * @return Request|null The rewritten request, the original one when nothing applies, or null.
 *
 * @example
 * Force one field from the route:
 * ```php
 * // POST /owners/7/items, body: ['name' => 'Chair']
 * $request = injectRouteValues( $request , $args , [ 'ownerId' => 'owner' ] ) ;
 * // body: ['name' => 'Chair', 'owner' => '7']
 * ```
 *
 * The route always wins over the body:
 * ```php
 * // POST /owners/7/items, body: ['owner' => '99']
 * $request = injectRouteValues( $request , $args , [ 'ownerId' => 'owner' ] ) ;
 * // body: ['owner' => '7']
 * ```
 *
 * Force a nested field with dot notation:
 * ```php
 * // POST /owners/7/items, body: ['owner' => ['id' => '99', 'name' => 'Alice']]
 * $request = injectRouteValues( $request , $args , [ 'ownerId' => 'owner.id' ] ) ;
 * // body: ['owner' => ['id' => '7', 'name' => 'Alice']]
 * ```
 *
 * Nothing to inject leaves the request as-is:
 * ```php
 * injectRouteValues( $request , [] , [ 'ownerId' => 'owner' ] ) === $request ; // true
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.1.0
 */
function injectRouteValues( ?Request $request , array $args , array $bindings ) :?Request
{
    if ( $request === null || $bindings === [] )
    {
        return $request ;
    }

    $body     = toAssociativeArray( $request->getParsedBody() ?? [] ) ;
    $injected = false ;

    foreach ( $bindings as $placeholder => $field )
    {
        $value = $args[ $placeholder ] ?? null ;

        if ( $field === Char::EMPTY || ( !is_int( $value ) && ( !is_string( $value ) || $value === Char::EMPTY ) ) )
        {
            continue ;
        }

        $body     = setKeyValue( $body , $field , (string) $value ) ;
        $injected = true ;
    }

    return $injected ? $request->withParsedBody( $body ) : $request ;
}