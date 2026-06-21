<?php

namespace oihana\controllers\enums ;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the option keys accepted by
 * {@see oihana\controllers\traits\ImageTrait::resize()} (and the image geometry keys).
 *
 * @package oihana\controllers\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ResizeOption
{
    use ConstantsTrait ;

    /**
     * The current image height, as returned by `Imagick::getImageGeometry()`.
     */
    public const string HEIGHT = 'height' ;

    /**
     * The maximum allowed height; larger images are scaled down. Default: 1200.
     */
    public const string MAX_HEIGHT = 'maxHeight' ;

    /**
     * The maximum allowed width; larger images are scaled down. Default: 1920.
     */
    public const string MAX_WIDTH = 'maxWidth' ;

    /**
     * The current image width, as returned by `Imagick::getImageGeometry()`.
     */
    public const string WIDTH = 'width' ;
}
