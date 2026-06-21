<?php

namespace oihana\controllers\traits\prepare;

use xyz\oihana\schema\Pagination;

use oihana\controllers\traits\LimitTrait;
use oihana\controllers\traits\PaginationTrait;
use oihana\enums\FilterOption;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the pagination `limit` and `offset` parameters of a controller request.
 *
 * This trait resolves an integer pagination value from the route arguments and lets the
 * request query string override it, clamped between the controller/pagination `minLimit`
 * and `maxLimit` bounds. Non-integer results fall back to the controller property, the
 * pagination object, then `$defaultValue`, and a request-provided value is recorded in the
 * parameter bag. The same logic serves both {@see Pagination::LIMIT} and
 * {@see Pagination::OFFSET}.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareLimit
{
    use PaginationTrait ,
        LimitTrait ;

    /**
     * Prepare and returns the 'limit' value.
     * @param Request|null $request      The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args         The route/controller arguments that may carry an initial pagination value.
     * @param array|null   $params       A reference to the parameter bag updated in place with the prepared value.
     * @param int          $defaultValue The fallback value used when no valid integer is resolved.
     * @param string       $property     The pagination property to read and store (defaults to {@see Pagination::LIMIT}).
     * @return int The resolved, range-clamped pagination value.
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
     * @param Request|null $request      The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args         The route/controller arguments that may carry an initial offset value.
     * @param array|null   $params       A reference to the parameter bag updated in place with the prepared value.
     * @param int          $defaultValue The fallback value used when no valid integer is resolved.
     * @return int The resolved, range-clamped offset value.
     */
    protected function prepareOffset( ?Request $request , array $args = [] , ?array &$params = null , int $defaultValue = 0 ) :int
    {
        return $this->prepareLimit( $request , $args , $params , $defaultValue , Pagination::OFFSET ) ;
    }
}