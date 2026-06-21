<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;

trait MockTrait
{
    /**
     * The mock flag to test the model.
     * @var bool
     */
    public ?bool $mock = null ;

    /**
     * Initialize the `mock` property.
     * @param bool|array $init
     * @return $this
     */
    public function initializeMock( bool|array $init = [] ) :static
    {
        $this->mock = is_bool( $init ) ? $init : ( $init[ ControllerParam::MOCK ] ?? null ) ;
        return $this ;
    }
}