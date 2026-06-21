<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\MockTrait;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Prepares the `mock` flag of a controller request.
 *
 * This boolean flag toggles the mock/simulation mode of an endpoint (returning fixture data
 * instead of hitting real services). The trait delegates to
 * {@see PrepareBoolean::prepareBoolean()} bound to the {@see ControllerParam::MOCK}
 * parameter, so the request query string drives the value and, when present, the normalized
 * boolean is written back into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareMock
{
    use MockTrait ;

    /**
     * Prepare and returns the 'mock' value.
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial `mock` value.
     * @param array|null   $params  A reference to the parameter bag updated in place with the prepared value.
     * @return bool|null The resolved `mock` flag.
     */
    protected function prepareMock( ?Request $request , array $args = [] , ?array &$params = null ) :?bool
    {
        return $this->prepareBoolean( $request  , $args , $params ,  ControllerParam::MOCK ) ;
    }
}