<?php

namespace oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;

trait ParamsTrait
{
    /**
     * The model reference.
     */
    public ?array $params = null ;

    /**
     * Initialize the params definition.
     * @param array $init
     * @return static
     */
    public function initializeParams( array $init = [] ) :static
    {
        $this->params = $init[ ControllerParam::PARAMS ] ?? $this->params ;
        return $this ;
    }
}