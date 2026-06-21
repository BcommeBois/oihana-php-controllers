<?php

namespace oihana\controllers\helpers ;

/**
 * Computes an HTTP entity tag (`ETag`) validator for a file.
 *
 * By default the tag is derived from the file **metadata** (`mtime`-`size`, in
 * hexadecimal) so no content read is required — this keeps large-file streaming
 * cheap. Pass `$hashContent = true` to build an exact, byte-level tag from
 * `md5_file()` instead (reads the whole file).
 *
 * The returned value is a quoted opaque tag, prefixed with `W/` when `$weak` is true.
 *
 * @param string $file        Absolute path of the file (must exist).
 * @param bool   $weak        Emit a weak validator (`W/"..."`) instead of a strong one.
 * @param bool   $hashContent Derive the tag from the file content (`md5_file()`) instead of its metadata.
 *
 * @return string The `ETag` value, e.g. `"18f-3e8"` or `W/"18f-3e8"`.
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @example
 * ```php
 * computeETag( '/var/data/file.bin' ) ;                       // "<mtime-hex>-<size-hex>" (strong, metadata)
 * computeETag( '/var/data/file.bin' , weak: true ) ;          // W/"<mtime-hex>-<size-hex>"
 * computeETag( '/var/data/file.bin' , hashContent: true ) ;   // "<md5>" (strong, exact)
 * ```
 */
function computeETag( string $file , bool $weak = false , bool $hashContent = false ) : string
{
    $hash = $hashContent
          ? md5_file( $file )
          : sprintf( '%x-%x' , filemtime( $file ) , filesize( $file ) ) ;

    $tag = '"' . $hash . '"' ;

    return $weak ? 'W/' . $tag : $tag ;
}
