<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;

/**
 * Provides a `mock` flag allowing a controller to switch its underlying model into a test/mock mode.
 *
 * The flag is typically used to return fabricated data instead of querying the real data source,
 * which is convenient for tests and previews.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait MockTrait
{
    /**
     * The mock flag to test the model.
     * @var bool
     */
    public ?bool $mock = null ;

    /**
     * Initialize the `mock` property.
     *
     * The flag is read directly when `$init` is a boolean, otherwise from the
     * `ControllerParam::MOCK` key of the initialization array (defaulting to `null`).
     *
     * @param bool|array $init Optional initialization array or the mock boolean value.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializeMock( bool|array $init = [] ) :static
    {
        $this->mock = is_bool( $init ) ? $init : ( $init[ ControllerParam::MOCK ] ?? null ) ;
        return $this ;
    }
}