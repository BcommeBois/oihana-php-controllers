<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PrepareBench
{
    use PrepareBoolean ;

    /**
     * Prepare and returns the 'bench' value.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return bool
     */
    protected function prepareBench( ?Request $request , array $args = [] , ?array &$params = null ) :bool
    {
        return $this->prepareBoolean( $request  , $args , $params , ControllerParam::BENCH ) ;
    }

}