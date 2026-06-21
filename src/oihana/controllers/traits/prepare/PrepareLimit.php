<?php

namespace oihana\controllers\traits\prepare;

use xyz\oihana\schema\Pagination;

use oihana\controllers\traits\LimitTrait;
use oihana\controllers\traits\PaginationTrait;
use oihana\enums\FilterOption;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareLimit
{
    use PaginationTrait ,
        LimitTrait ;

    /**
     * Prepare and returns the 'limit' value.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @param int $defaultValue
     * @param string $property
     * @return int
     */
    protected function prepareLimit
    (
        ?Request  $request ,
           array  $args         = [] ,
          ?array  &$params      = null ,
             int  $defaultValue = 0 ,
          string  $property     = Pagination::LIMIT
    ) :int
    {
        $value = $args[ $property ] ?? null ;

        $flag = false ;
        if( isset( $request ) )
        {
            $param = getQueryParam( $request , $property ); // query param only (not body).
            if( isset( $param ) )
            {
                $flag = true ;
                $value = filter_var
                (
                    $param ,
                    FILTER_VALIDATE_INT ,
                    [
                        FilterOption::OPTIONS =>
                        [
                            FilterOption::MIN_RANGE => $this->minLimit ?? $this->pagination?->minLimit ?? 0   ,
                            FilterOption::MAX_RANGE => $this->maxLimit ?? $this->pagination?->maxLimit ?? 100
                        ]
                    ]
                );
            }
        }

        if( !is_int( $value ) )
        {
            $value = intval( $this->{ $property } ?? $this->pagination?->{ $property } ?? $defaultValue );
        }

        if( $flag )
        {
            $params[ $property ] = $value ;
        }

        return $value ;
    }

    /**
     * Prepare and returns the 'offset' value.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @param int $defaultValue
     * @return int
     */
    protected function prepareOffset( ?Request $request , array $args = [] , ?array &$params = null , int $defaultValue = 0 ) :int
    {
        return $this->prepareLimit( $request , $args , $params , $defaultValue , Pagination::OFFSET ) ;
    }
}