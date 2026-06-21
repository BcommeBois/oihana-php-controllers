<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\FilterOption;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the `interval` parameter of a time-based controller request.
 *
 * This trait reads the {@see ControllerParam::INTERVAL} query parameter and validates it
 * as an integer constrained between `1` and the `maxRange` supplied by `$timeOptions`. When
 * no valid value is provided it falls back to the {@see ControllerParam::INTERVAL_DEFAULT}
 * entry of `$timeOptions`, and a request-provided value is mirrored into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareInterval
{
    /**
     * Prepares the `interval` parameter from the request.
     *
     * @param Request|null $request     The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $params      A reference to the parameter bag updated in place with the resolved interval.
     * @param string|null  $interval    A reference to the interval holder assigned in place with the validated value.
     * @param array|null   $timeOptions The time configuration providing the {@see FilterOption::MAX_RANGE} bound and default interval.
     * @return void
     */
    protected function prepareInterval(?Request $request , array &$params , ?string &$interval , ?array $timeOptions ) :void
    {
        $register = false ;
        if( isset( $request ) )
        {
            $value = getQueryParam( $request , ControllerParam::INTERVAL ) ;
            if( isset( $value ) )
            {
                $register = true ;
                $interval = filter_var( $value , FILTER_VALIDATE_INT ,
                [
                    FilterOption::OPTIONS =>
                    [
                        FilterOption::MIN_RANGE => 1,
                        FilterOption::MAX_RANGE => intval( $timeOptions[ FilterOption::MAX_RANGE ] )
                    ]
                ]) ;
            }
        }

        if( is_null($interval) || $interval === false )
        {
            $interval = (int) $timeOptions[ ControllerParam::INTERVAL_DEFAULT ] ;
        }

        if( $register )
        {
            $params[ ControllerParam::INTERVAL ] = $interval ;
        }
    }
}