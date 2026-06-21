<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\LanguagesTrait;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareLang
{
    use LanguagesTrait ;

    protected function prepareLang( ?Request $request , array $args = [] , ?array &$params = null ) :?string
    {
        $lang = $args[ ControllerParam::LANG ] ?? null ;
        if( isset( $request ) )
        {
            $value = getQueryParam( $request , ControllerParam::LANG ) ; // query param only (not body)
            if( !empty( $value ) )
            {
                if( in_array( $value , $this->languages ) )
                {
                    $lang = strtolower( $value ) ;
                }
                else if( strtolower( $value ) == ControllerParam::ALL )
                {
                    $lang = null ;
                }
            }

            if( !empty( $lang ) && $params )
            {
                $params[ ControllerParam::LANG ] = $lang ;
            }
        }
        return $lang ;
    }
}