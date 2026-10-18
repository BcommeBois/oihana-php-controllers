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
     * When set, the type read from the content is validated with `oihana\files\validateMimeType()`
     * before the file takes its place ; a refused file is removed.
     */
    public const string ALLOWED_MIME_TYPES = 'allowedMimeTypes' ;

    /**
     * Store the file under the name the client gave it (bool). Default: false — a random name.
     *
     * The part before the extension is cleaned by `oihana\core\strings\slugify()` (no leading dot,
     * no dot inside, ASCII lower-case, at most 200 characters ; nothing left gives a random name),
     * and the extension is the one of the type read from the content — kept when the client's
     * matches, replaced otherwise, added when missing. A name already taken is numbered
     * (`photo-2.jpg`), unless {@see self::OVERWRITE} is set.
     */
    public const string CLIENT_FILENAME = 'clientFilename' ;

    /**
     * Extensions to use for some MIME types (array of `type => '.ext'`), read before the catalogue
     * of `oihana\files\enums\FileMimeType` — for a type it does not know, or to prefer one of
     * several (`[ 'audio/x-wav' => '.wav' ]`). Types are compared by their reference name
     * (`oihana\files\normalizeMimeType()`) ; an extension must be a dot followed by letters and
     * digits.
     */
    public const string EXTENSIONS = 'extensions' ;

    /**
     * The name of the stored file (single upload only). Default: a random name.
     *
     * The name is kept or refused, never altered : its directories are dropped, and it is refused
     * when it starts with a dot, or when its extension does not match the type read from the
     * content. A name already taken is refused, unless {@see self::OVERWRITE} is set.
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
