<?php

namespace oihana\controllers\traits\prepare;

use DI\NotFoundException;
use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getParam;

/**
 * Prepares the `groupBy` parameter of a controller request.
 *
 * This trait reads the {@see ControllerParam::GROUP_BY} value from the request (query or
 * body, via {@see getParam()}). When a non-empty value is found it is assigned to the
 * referenced `$groupBy` variable and mirrored into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareGroupBy
{
    /**
     * Prepares the `groupBy` parameter from the request.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array|null   $params  A reference to the parameter bag updated in place with the resolved group-by value.
     * @param string|null  $groupBy A reference to the group-by holder assigned in place when the request provides a value.
     *
     * @return void
     *
     * @throws NotFoundException If the underlying parameter resolution fails to locate a required dependency.
     */
    protected function prepareGroupBy( ?Request $request , ?array &$params , ?string &$groupBy ) :void
    {
        if( isset( $request ) )
        {
            $value = getParam( $request , ControllerParam::GROUP_BY );
            if( isset( $value ) )
            {
                $groupBy = $value ;
                if( !empty( $groupBy ) && $params )
                {
                    $params[ ControllerParam::GROUP_BY ] = $groupBy ;
                }
            }
        }
    }
}