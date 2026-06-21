<?php

namespace oihana\controllers\traits;

use oihana\enums\http\HttpParamStrategy;

/**
 * Provides a property to retrieve params in the Http request parameters or body.
 *
 * The strategy controls where a controller reads request parameters from: the query string,
 * the request body, or both.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
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
     * @param string|array|null $strategy Either a string strategy or an array carrying it under the {@see self::PARAMS_STRATEGY} key. Invalid values are ignored and the current strategy is kept.
     *
     * @return static Returns the current instance for method chaining.
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