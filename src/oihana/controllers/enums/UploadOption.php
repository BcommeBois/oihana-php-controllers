<?php

namespace oihana\controllers\enums ;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the option keys accepted by the upload helpers
 * ({@see oihana\controllers\traits\UploadTrait}).
 *
 * @package oihana\controllers\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class UploadOption
{
    use ConstantsTrait ;

    /**
     * Whitelist of allowed MIME types (array of strings or arrays of strings).
     * When set, the stored file is validated with `oihana\files\validateMimeType()`.
     */
    public const string ALLOWED_MIME_TYPES = 'allowedMimeTypes' ;

    /**
     * Override the stored file name (single upload only). Defaults to the
     * sanitized client file name.
     */
    public const string FILENAME = 'filename' ;

    /**
     * Maximum allowed file size in bytes. Larger uploads are rejected. Default: no limit.
     */
    public const string MAX_SIZE = 'maxSize' ;

    /**
     * Whether an existing target file may be overwritten. Default: false.
     */
    public const string OVERWRITE = 'overwrite' ;
}
