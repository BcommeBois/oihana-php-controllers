<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\MockTrait;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PrepareMock
{
    use MockTrait ;

    /**
     * Prepare and returns the 'mock' value.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return ?bool
     */
    protected function prepareMock( ?Request $request , array $args = [] , ?array &$params = null ) :?bool
    {
        return $this->prepareBoolean( $request  , $args , $params ,  ControllerParam::MOCK ) ;
    }
}