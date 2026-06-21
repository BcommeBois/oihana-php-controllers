<?php

namespace oihana\controllers\traits\prepare;

use oihana\enums\FilterOption;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareInt
{
    /**
     * Prepare an integer parameter.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @param int|null $defaultValue
     * @param string|null $name
     * @return int|null
     */
    protected function prepareInt( ?Request $request , array $args = [] , ?array &$params = null , ?int $defaultValue = null , ?string $name = null ) :?int
    {
        $value   = $args[ $name ] ?? $this->{ $name } ?? $defaultValue ;
        $options = $args[ FilterOption::OPTIONS ] ?? null ;
        $flag    = false ;

        if( isset( $request ) )
        {
            $param = getQueryParam( $request , $name ) ;
            if( isset( $param ) )
            {
                $flag = true ;
                $value = filter_var( $param , FILTER_VALIDATE_INT , [ FilterOption::OPTIONS => $options ] ) ;
            }
        }

        if( !is_int( $value ) )
        {
            $value = $defaultValue ;
        }

        if( $flag )
        {
            $params[ $name ] = $value ;
        }

        return $value ;
    }
}