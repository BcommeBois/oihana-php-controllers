<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PrepareMargin
{
    use PrepareBoolean ;

    /**
     * Prepare and returns the 'margin' flag value.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return ?bool
     */
    protected function prepareMargin( ?Request $request , array $args = [] , ?array &$params = null ) :?bool
    {
        return $this->prepareBoolean( $request , $args , $params ,  ControllerParam::MARGIN ) ;
    }
}