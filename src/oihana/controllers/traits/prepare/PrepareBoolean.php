<?php

namespace oihana\controllers\traits\prepare;

use oihana\enums\Boolean;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareBoolean
{
    /**
     * Prepare and returns the boolean value.
     * @param Request|null $request
     * @param array $args
     * @param ?array $params
     * @param ?string $name
     * @return ?bool
     */
    protected function prepareBoolean( ?Request $request , array $args = [] , ?array &$params = null , ?string $name = null ) :?bool
    {
        $property = $args[ $name ] ?? $this?->{ $name } ?? false ;

        $flag = false ;
        if( isset( $request ) )
        {
            $value = getQueryParam( $request , $name ) ; // query param only (not body).
            if( isset( $value ) )
            {
                $flag = true ;
                $property = filter_var( $value , FILTER_VALIDATE_BOOLEAN , FILTER_NULL_ON_FAILURE ) ?? $property ;
            }
        }

        if( $flag )
        {
            $params[ $name ] = $property ? Boolean::TRUE : Boolean::FALSE ;
        }

        return $property ;
    }
}