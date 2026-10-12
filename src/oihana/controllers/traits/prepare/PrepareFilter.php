<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\exceptions\http\Error400;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\getQueryParam;
use function oihana\core\strings\urlencode;

/**
 * Prepares the `filter` parameter of a controller request.
 *
 * This trait expects the {@see ControllerParam::FILTER} query parameter to be a JSON
 * encoded object or list. When it is valid, the decoded structure is returned and the raw
 * JSON string is recorded in the parameter bag. A filter that cannot be read — not JSON,
 * or JSON that is neither an object nor a list — is refused with an {@see Error400}:
 * ignoring it would answer the whole, unfiltered collection as if it were the filtered one.
 * An absent or empty parameter, or the JSON `null`, means "no filter": the route argument
 * value (or null) is returned instead.
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
     * @return array|null The decoded filter definition, or the fallback argument value when no filter is provided.
     *
     * @throws Error400 If the `filter` parameter is not valid JSON, or is JSON but neither an object nor a list.
     */
    protected function prepareFilter( ?Request $request , array $args = [] , ?array &$params = null ) :?array
    {
        if( isset( $request ) )
        {
            $param = getQueryParam( $request , ControllerParam::FILTER ) ;
            if( is_string( $param ) && $param !== '' )
            {
                if( !json_validate( $param ) )
                {
                    throw new Error400( 'The filter parameter is not valid JSON.' ) ;
                }

                $value = json_decode( $param , true ) ;

                if( is_array( $value ) )
                {
                    $params[ ControllerParam::FILTER ] = $param ;
                    return $value ;
                }

                if( $value !== null )
                {
                    throw new Error400
                    (
                        sprintf( 'The filter parameter must be a JSON object or list, %s given.' , get_debug_type( $value ) )
                    ) ;
                }
            }
        }
        return $args[ ControllerParam::FILTER ] ?? null ;
    }
}