<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareSearch
{
    protected function prepareSearch( ?Request $request , array $args = [] , ?array &$params = null ) :?string
    {
        $search = $args[ ControllerParam::SEARCH ] ?? null ;
        if( isset( $request ) )
        {
            $search = getQueryParam( $request , ControllerParam::SEARCH ) ; // query param only (not body)
            if( isset( $search ) && is_array( $params ) )
            {
                $params[ ControllerParam::SEARCH ] = $search ;
            }
        }
        return $search ;
    }
}