<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;

trait PrepareHasTotal
{
    use PrepareBoolean ;

    /**
     * Indicates if the list method return the total number of elements.
     * @var bool
     */
    public bool $hasTotal = true ;

    /**
     * Initialize the hasTotal property with an associative array definition.
     * @param array $init
     * @return static
     */
    public function initializeHasTotal( array $init = [] ):static
    {
        $this->hasTotal = $init[ ControllerParam::HAS_TOTAL ] ?? $this->hasTotal ;
        return $this ;
    }

    /**
     * Prepare and returns the 'hasTotal' value.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return bool
     */
    public function prepareHasTotal( ?Request $request , array $args = [] , ?array &$params = null ) :bool
    {
        return $this->prepareBoolean( $request , $args , $params , ControllerParam::HAS_TOTAL ) ;
    }
}