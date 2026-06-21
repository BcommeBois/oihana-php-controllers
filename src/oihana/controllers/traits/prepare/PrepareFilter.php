<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getQueryParam;
use function oihana\core\strings\urlencode;

trait PrepareFilter
{
    /**
     * Prepare a filter parameter.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return array|null
     */
    protected function prepareFilter( ?Request $request , array $args = [] , ?array &$params = null ) :?array
    {
        if( isset( $request ) )
        {
            $param = getQueryParam( $request , ControllerParam::FILTER ) ;
            if( is_string( $param )  )
            {
                if( json_validate( $param ) )
                {
                    $value = json_decode( $param , true ) ;
                    if( is_array( $value ) )
                    {
                        $params[ ControllerParam::FILTER ] = $param ;
                        return $value ;
                    }
                    else
                    {
                        $this->logger->warning
                        (
                            __METHOD__ . ' failed, the parameter is valid JSON but not an array/object: ' . $param
                        );
                    }
                }
                else
                {
                    $this->logger->warning( __METHOD__ . ' failed, the parameter is not a valid JSON expression' ) ;
                }
            }
        }
        return $args[ ControllerParam::FILTER ] ?? null ;
    }
}