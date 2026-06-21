<?php

namespace oihana\controllers\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of all the common controller's parameters.
 */
class TwigParam
{
    use ConstantsTrait;

    public const string BACKGROUND_COLOR = 'backgroundColor' ;
    public const string FULL_PATH        = 'fullPath' ;
    public const string LOGO             = 'logo' ;
    public const string LOGO_DARK        = 'logoDark' ;
    public const string PATTERN_COLOR    = 'patternColor' ;
    public const string TWIG             = 'twig' ;
}