<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getQueryParam;
use function oihana\core\strings\urlencode;

/**
 * Prepares the `filter` parameter of a controller request.
 *
 * This trait expects the {@see ControllerParam::FILTER} query parameter to be a JSON
 * encoded array/object. When it is valid, the decoded structure is returned and the raw
 * JSON string is recorded in the parameter bag; malformed or non-array JSON is logged as
 * a warning and the route argument value (or null) is returned instead.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareFilter
{
    /**
     * Prepares and returns the `filter` parameter.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry a fallback `filter` value.
     * @param array|null   $params  A reference to the parameter bag updated in place with the raw JSON filter string.
     *
     * @return array|null The decoded filter definition, or the fallback argument value when no valid filter is provided.
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