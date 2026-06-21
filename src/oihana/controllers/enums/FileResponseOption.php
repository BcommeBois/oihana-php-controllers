<?php

namespace oihana\controllers\enums ;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the option keys accepted by the file/binary response helpers
 * (e.g. {@see FileTrait::fileResponse()}).
 *
 * These keys drive which content headers a download response emits.
 *
 * @package oihana\controllers\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class FileResponseOption
{
    use ConstantsTrait ;

    /**
     * The `Content-Disposition` header value to send when {@see self::USE_CONTENT_DISPOSITION} is enabled.
     */
    public const string CONTENT_DISPOSITION = 'contentDisposition' ;

    /**
     * The output format (e.g. `jpg`) used to build the `image/<format>` content type. Default: `jpg`.
     */
    public const string FORMAT = 'format' ;

    /**
     * Whether to add a `Content-Disposition` header to the response. Default: false.
     */
    public const string USE_CONTENT_DISPOSITION = 'useContentDisposition' ;

    /**
     * Whether to add a `Content-Length` header (the file size) to the response. Default: false.
     */
    public const string USE_CONTENT_LENGTH = 'useContentLength' ;

    /**
     * Whether to add a `Content-Type` header (the detected MIME type) to the response. Default: false.
     */
    public const string USE_CONTENT_TYPE = 'useContentType' ;
}
