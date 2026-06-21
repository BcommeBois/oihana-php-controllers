<?php

namespace oihana\controllers\traits;

use oihana\enums\http\HttpParamStrategy;

/**
 * Provides a property to retrieve params in the Http request parameters or body.
 *
 * @package oihana\controllers\traits
 */
trait ParamsStrategyTrait
{
    /**
     * Strategy to fetch parameters: 'both' (default), 'body' only, or 'query' only.
     * @var string
     */
    public string $paramsStrategy = HttpParamStrategy::BOTH ;

    /**
     * The 'paramsStrategy' parameter.
     */
    public const string PARAMS_STRATEGY = 'paramsStrategy' ;

    /**
     * Initialize the params strategy : 'both' (default), 'body' (only), 'query' (only).
     *
     * @param string|array|null $strategy $strategy Either a string strategy or an array with key ControllerParam::PARAMS_STRATEGY.
     *
     * @return static
     */
    public function initializeParamsStrategy( string|array|null $strategy = null ) :static
    {
        if( is_array( $strategy ) )
        {
            $strategy = $strategy[ self::PARAMS_STRATEGY ] ?? $this->paramsStrategy ;
        }
        $this->paramsStrategy = HttpParamStrategy::includes( $strategy ) ? $strategy : $this->paramsStrategy ;
        return $this ;
    }
}