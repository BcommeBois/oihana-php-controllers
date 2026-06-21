<?php

namespace oihana\controllers\enums;

use oihana\controllers\enums\traits\ControllerParamTrait;
use oihana\reflect\traits\ConstantsTrait;

use xyz\oihana\schema\constants\traits\PaginationTrait;

/**
 * The enumeration of all the common controller's parameters.
 */
class ControllerParam
{
    use ConstantsTrait ,
        ControllerParamTrait ,
        PaginationTrait;
}