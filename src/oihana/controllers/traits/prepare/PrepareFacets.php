<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ParamsTrait;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the facet definitions of a controller request.
 *
 * This trait merges the facets carried by the route arguments with two request-driven
 * sources: the per-parameter facet definitions declared in the controller `params`
 * mapping (see {@see prepareParamsFacets()}), and a raw JSON `facets` query parameter.
 * Each contributing query value is URL-encoded and recorded in the parameter bag, while
 * invalid JSON is logged as a warning and ignored.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareFacets
{
    use ParamsTrait;

    /**
     * Prepares and returns the facet definitions.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial set of facets.
     * @param array|null   $params  A reference to the parameter bag updated in place with the URL-encoded facet payloads.
     *
     * @return array|null The merged facet definitions.
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
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments, optionally carrying a {@see ControllerParam::PARAMS} mapping.
     * @param array        $facets  A reference to the facet collection enriched in place from the matching query parameters.
     * @param array|null   $params  A reference to the parameter bag updated in place with the URL-encoded facet payloads.
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

