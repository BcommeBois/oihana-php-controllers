<?php

namespace oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;

/**
 * Holds the default request parameters definition of a controller.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ParamsTrait
{
    /**
     * The default parameters definition.
     * @var array|null
     */
    public ?array $params = null ;

    /**
     * Initializes the params definition from an array.
     *
     * @param array $init Initialization array, read from the {@see ControllerParam::PARAMS} key.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializeParams( array $init = [] ) :static
    {
        $this->params = $init[ ControllerParam::PARAMS ] ?? $this->params ;
        return $this ;
    }
}