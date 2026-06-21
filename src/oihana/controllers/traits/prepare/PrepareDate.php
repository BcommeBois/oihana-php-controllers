<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use org\schema\constants\Prop;

use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getQueryParam;
use function oihana\core\date\isDate;

trait PrepareDate
{
    protected function prepareDate
    (
        ?Request $request ,
         array   $args    = [] ,
        ?array   &$params = null ,
        ?string  $default = null ,
         string  $name    = Prop::DATE
    )
    :?string
    {
        $format = $args[ ControllerParam::DATE_FORMAT ] ?? 'Y-m-d' ;
        $value  = $args[ $name ] ?? $this->{ $name } ?? $default  ;
        $value  = isDate( $value , $format ) ? $value : date( $format ) ;

        $flag = false ;

        if( $request )
        {
            $queryParam = getQueryParam( $request , $name ) ;
            if( isDate( $queryParam , $format ) )
            {
                $value = $queryParam ;
                $flag = true ;
            }
        }

        if( isset( $value ) && is_array( $params ) && $flag )
        {
            $params[ $name ] = $value ;
        }

        return $value ;
    }
}