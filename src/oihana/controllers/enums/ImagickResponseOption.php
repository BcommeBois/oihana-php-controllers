<?php

namespace oihana\controllers\enums ;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the Imagick transform option keys accepted by
 * {@see ImageTrait::imagickResponse()}.
 *
 * @package oihana\controllers\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ImagickResponseOption
{
    use ConstantsTrait ;

    /**
     * The Imagick compression constant to apply (e.g. `Imagick::COMPRESSION_JPEG`).
     */
    public const string COMPRESSION = 'compression' ;

    /**
     * Whether to desaturate the image to grayscale. Default: false.
     */
    public const string GRAY = 'gray' ;

    /**
     * The compression quality (0-100). Default: 70.
     */
    public const string QUALITY = 'quality' ;

    /**
     * Whether to strip the image of profiles and comments. Default: false.
     */
    public const string STRIP = 'strip' ;
}
