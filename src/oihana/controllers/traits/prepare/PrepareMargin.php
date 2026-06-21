<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Prepares the `margin` flag of a controller request.
 *
 * This boolean flag toggles the margin-related behavior of an endpoint. The trait delegates
 * to {@see PrepareBoolean::prepareBoolean()} bound to the {@see ControllerParam::MARGIN}
 * parameter, so the request query string drives the value and, when present, the normalized
 * boolean is written back into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareMargin
{
    use PrepareBoolean ;

    /**
     * Prepare and returns the 'margin' flag value.
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial `margin` value.
     * @param array|null   $params  A reference to the parameter bag updated in place with the prepared value.
     * @return bool|null The resolved `margin` flag.
     */
    protected function prepareMargin( ?Request $request , array $args = [] , ?array &$params = null ) :?bool
    {
        return $this->prepareBoolean( $request , $args , $params ,  ControllerParam::MARGIN ) ;
    }
}