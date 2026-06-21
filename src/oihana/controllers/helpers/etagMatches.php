<?php

namespace oihana\controllers\helpers ;

/**
 * Tells whether an `If-None-Match` header value matches a given `ETag`.
 *
 * Implements the RFC 7232 §3.2 semantics used for `If-None-Match`:
 * - `*` matches any current representation;
 * - otherwise the header is a comma-separated list of entity tags, compared with the
 *   **weak comparison function** — the `W/` prefix is ignored on both sides, so
 *   `W/"x"` and `"x"` are considered a match.
 *
 * @param string $header The raw `If-None-Match` header value.
 * @param string $etag   The current `ETag` of the resource (as produced by {@see computeETag()}).
 *
 * @return bool `true` when the precondition matches (the caller should answer `304 Not Modified`).
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @example
 * ```php
 * etagMatches( '*'             , '"abc"' ) ; // true  (matches any representation)
 * etagMatches( '"abc", "def"'  , '"abc"' ) ; // true  (present in the list)
 * etagMatches( 'W/"abc"'       , '"abc"' ) ; // true  (weak comparison)
 * etagMatches( '"xyz"'         , '"abc"' ) ; // false (no match)
 * etagMatches( ''              , '"abc"' ) ; // false (no precondition)
 * ```
 */
function etagMatches( string $header , string $etag ) : bool
{
    $header = trim( $header ) ;

    if ( $header === '' )
    {
        return false ;
    }

    if ( $header === '*' )
    {
        return true ;
    }

    $strip  = fn( string $tag ) : string => trim( preg_replace( '/^W\//' , '' , trim( $tag ) ) ) ;
    $needle = $strip( $etag ) ;

    foreach ( explode( ',' , $header ) as $candidate )
    {
        if ( $strip( $candidate ) === $needle )
        {
            return true ;
        }
    }

    return false ;
}
