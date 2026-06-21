<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ParamsTrait;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareFacets
{
    use ParamsTrait;

    /**
     * Prepare the facets definitions.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return array|null
     */
    protected function prepareFacets( ?Request $request , array $args = [] , ?array &$params = [] ) :?array
    {
        $facets = $args[ ControllerParam::FACETS ] ?? [] ;
        if( isset( $request ) )
        {
            // ----------- Use the parameters in the url query to inject facets
            $this->prepareParamsFacets( $request , $args , $facets , $params ) ;
            // -----------
            $values = getQueryParam( $request , ControllerParam::FACETS ) ;
            if( is_string( $values ) )
            {
                if( json_validate( $values ) )
                {
                    $params[ControllerParam::FACETS  ] = urlencode( $values ) ;
                    $values = json_decode( $values , true ) ;
                    if( is_array( $values ) )
                    {
                        $facets = [ ...$facets , ...$values ] ;
                    }
                }
                else
                {
                    $this->logger->warning( __METHOD__ . ' failed, the facets params is not a valid json expression: ' . json_encode( $facets ) );
                }
            }
        }
        return $facets ;

    }

    /**
     * Try to creates the facets definition with the $this->params array definition of the controller.
     * Target all 'facets' definitions.
     * @param Request|null $request
     * @param array $args
     * @param array $facets
     * @param array|null $params
     * @return void
     * @example
     * To list with the controller multiple things by ids,
     * you can invoke the route url : https://myapi/products?id=[12,255,300]
     *
     * 1 - Initialize the facets in the model definition :
     * ```
     * FACETS =>
     * [
     *     Prop::ID =>
     *     [
     *          Facet::TYPE       => Facet::IN ,
     *          Facet::EXPRESSION => [ SQL::COLUMN => $primaryKey , SQL::TABLE => $tableAlias , SQL::ALTER => StringFunction::RTRIM   ] ,
     *     ] ,
     * ]
     * ```
     * 2 - Defines the paramters behavior in the controller definition :
     * ```
     * ControllerParam::PARAMS => [ Prop::ID => ControllerParam::FACETS ]
     * ```
     */
    protected function prepareParamsFacets( ?Request $request , array $args = [] , array &$facets = [] , ?array &$params = [] ) :void
    {
        if( isset( $request ) )
        {
            $definitions = $args[ ControllerParam::PARAMS ] ?? $this->params ;
            if( is_array( $definitions ) && count( $definitions ) > 0 )
            {
                $paramsDefinition = array_filter( $definitions , fn( $item ) => $item == ControllerParam::FACETS || ( isset( $item[ ControllerParam::TYPE ] ) && $item[ ControllerParam::TYPE ] == ControllerParam::FACETS ) ) ;
                foreach( $paramsDefinition as $key => $definition )
                {
                    $value = getQueryParam( $request , $key ) ;
                    if( isset( $value ) && json_validate( $value ) )
                    {
                        $params[ $key ] = urlencode( $value ) ;
                        $facets[ $key ] = json_decode( $value , true ) ;
                    }
                }
            }
        }
    }
}

