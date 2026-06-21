<?php

namespace oihana\controllers\traits\prepare;

use DI\NotFoundException;
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ApiTrait;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getParam;

/**
 * Prepares the `order` (sort direction) parameter of a controller request.
 *
 * This trait reads the {@see ControllerParam::ORDER} value from the request and validates
 * it, uppercased, against the whitelist of accepted directions declared in the controller
 * `api` configuration ({@see ControllerParam::ORDERS}). A recognized value is assigned to
 * the referenced `$order` variable and mirrored into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareOrder
{
    use ApiTrait ;

    /**
     * Prepares the `order` direction from the request.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array|null   $params  A reference to the parameter bag updated in place with the resolved order direction.
     * @param mixed        $order   A reference to the order holder assigned in place when the request supplies an accepted direction.
     * @return void
     *
     * @throws NotFoundException If the underlying parameter resolution fails to locate a required dependency.
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