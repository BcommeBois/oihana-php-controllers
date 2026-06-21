<?php

namespace oihana\controllers\helpers ;

/**
 * Parses an HTTP `Range` header into a single satisfiable byte interval.
 *
 * Supports the common single-range forms: `bytes=0-499`, `bytes=500-` (open-ended)
 * and `bytes=-500` (suffix / last N bytes). Multi-range requests are intentionally
 * not honored and resolve to `null` (the caller should serve the full content).
 *
 * @param string $rangeHeader The raw `Range` header value.
 * @param int    $fileSize    The total size of the file in bytes.
 *
 * @return array{0:int,1:int}|false|null
 *         - `[start, end]` (inclusive, clamped) for a satisfiable single range → `206`;
 *         - `false` when a range is present but unsatisfiable → `416`;
 *         - `null` when there is no usable single range (absent / malformed / multi-range) → full `200`.
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @example
 * ```php
 * parseRangeHeader( 'bytes=0-4'    , 11 ) ; // [0, 4]   -> 206
 * parseRangeHeader( 'bytes=6-'     , 11 ) ; // [6, 10]  -> 206 (open-ended)
 * parseRangeHeader( 'bytes=-5'     , 11 ) ; // [6, 10]  -> 206 (last 5 bytes)
 * parseRangeHeader( 'bytes=0-9999' , 11 ) ; // [0, 10]  -> 206 (end clamped)
 * parseRangeHeader( 'bytes=99-'    , 11 ) ; // false    -> 416 (unsatisfiable)
 * parseRangeHeader( 'bytes=0-2,5-' , 11 ) ; // null     -> 200 (multi-range ignored)
 * parseRangeHeader( ''             , 11 ) ; // null     -> 200 (no range)
 * ```
 */
function parseRangeHeader( string $rangeHeader , int $fileSize ) : array|false|null
{
    $rangeHeader = trim( $rangeHeader ) ;

    if ( $rangeHeader === '' || !str_starts_with( $rangeHeader , 'bytes=' ) )
    {
        return null ; // no byte range -> full content
    }

    $spec = substr( $rangeHeader , 6 ) ; // strip "bytes="

    if ( str_contains( $spec , ',' ) || !str_contains( $spec , '-' ) )
    {
        return null ; // multi-range or malformed -> full content
    }

    [ $startStr , $endStr ] = explode( '-' , $spec , 2 ) ;
    $startStr = trim( $startStr ) ;
    $endStr   = trim( $endStr ) ;

    if ( $startStr === '' ) // suffix form: -N (last N bytes)
    {
        if ( $endStr === '' || !ctype_digit( $endStr ) )
        {
            return null ;
        }
        $suffix = (int) $endStr ;
        if ( $suffix === 0 )
        {
            return false ; // "bytes=-0" is unsatisfiable
        }
        $start = max( 0 , $fileSize - $suffix ) ;
        $end   = $fileSize - 1 ;
    }
    else
    {
        if ( !ctype_digit( $startStr ) )
        {
            return null ;
        }
        $start = (int) $startStr ;
        if ( $endStr === '' )
        {
            $end = $fileSize - 1 ;
        }
        else
        {
            if ( !ctype_digit( $endStr ) )
            {
                return null ;
            }
            $end = min( (int) $endStr , $fileSize - 1 ) ;
        }
    }

    if ( $fileSize === 0 || $start > $end || $start >= $fileSize )
    {
        return false ; // unsatisfiable -> 416
    }

    return [ $start , $end ] ;
}
