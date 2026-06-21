<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the `search` parameter of a controller request.
 *
 * This trait reads the {@see ControllerParam::SEARCH} value from the route arguments and
 * lets the request query string override it. When the request supplies a value it is
 * recorded in the parameter bag (when one is provided).
 *
 * Only the query string is inspected (the request body is ignored).
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareSearch
{
    /**
     * Prepares and returns the `search` value.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial `search` value.
     * @param array|null   $params  A reference to the parameter bag updated in place with the prepared search term.
     * @return string|null The resolved search term, or null when none is provided.
     */
    protected function prepareSearch( ?Request $request , array $args = [] , ?array &$params = null ) :?string
    {
        $search = $args[ ControllerParam::SEARCH ] ?? null ;
        if( isset( $request ) )
        {
            $search = getQueryParam( $request , ControllerParam::SEARCH ) ; // query param only (not body)
            if( isset( $search ) && is_array( $params ) )
            {
                $params[ ControllerParam::SEARCH ] = $search ;
            }
        }
        return $search ;
    }
}