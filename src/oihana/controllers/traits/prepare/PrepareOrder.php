<?php

namespace oihana\controllers\traits\prepare;

use DI\NotFoundException;
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ApiTrait;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getParam;

trait PrepareOrder
{
    use ApiTrait ;

    /**
     * @throws NotFoundException
     */
    protected function prepareOrder( ?Request $request , ?array &$params , &$order ) :void
    {
        if( isset( $request ) )
        {
            $value = getParam( $request , ControllerParam::ORDER );

            if( !empty( $value ) )
            {
                $upper = strtoupper( $value )  ;

                $orders = $this->api[ ControllerParam::ORDERS ] ?? null ; // use property or an method argument

                if( is_array( $orders ) && in_array( $upper , $orders ) )
                {
                    $order = $upper ;
                }

                $params[ ControllerParam::ORDER ] = $order ;
            }
        }
    }
}