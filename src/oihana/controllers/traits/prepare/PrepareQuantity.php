<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PrepareQuantity
{
    use PrepareInt ;

    /**
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @param int|null $defaultValue
     * @return int|null
     */
    protected function prepareQuantity( ?Request $request , array $args = [] , ?array &$params = null , ?int $defaultValue = null ) :?int
    {
        return $this->prepareInt( $request , $args , $params , $defaultValue , ControllerParam::QUANTITY ) ;
    }
}