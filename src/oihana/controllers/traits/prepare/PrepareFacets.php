<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ParamsTrait;
use oihana\exceptions\http\Error400;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the facet definitions of a controller request.
 *
 * This trait merges the facets carried by the route arguments with two request-driven
 * sources: the per-parameter facet definitions declared in the controller `params`
 * mapping (see {@see prepareParamsFacets()}), and a raw JSON `facets` query parameter.
 * Each contributing query value is URL-encoded and recorded in the parameter bag.
 *
 * Facets restrict the list, so a facet that cannot be read is refused with an
 * {@see Error400} rather than ignored — ignoring it would answer the whole collection as if
 * it were the restricted one. The `facets` parameter must be a JSON object or list; a
 * parameter declared as a facet may also be a JSON scalar (`?id=12`). An absent or empty
 * parameter means "no facet", as does the JSON `null` for `facets`.
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
     *
     * @throws Error400 If the `facets` parameter, or a parameter declared as a facet, cannot be read.
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

            if( is_string( $values ) && $values !== '' )
            {
                if( !json_validate( $values ) )
                {
                    throw new Error400( 'The facets parameter is not valid JSON.' ) ;
                }

                $decoded = json_decode( $values , true ) ;

                if( is_array( $decoded ) )
                {
                    $params[ ControllerParam::FACETS ] = urlencode( $values ) ;
                    $facets = [ ...$facets , ...$decoded ] ;
                }
                else if( $decoded !== null )
                {
                    throw new Error400
                    (
                        sprintf( 'The facets parameter must be a JSON object or list, %s given.' , get_debug_type( $decoded ) )
                    ) ;
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
     * @throws Error400 If a parameter declared as a facet is present but is not valid JSON.
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
                    if( !isset( $value ) || $value === '' )
                    {
                        continue ;
                    }

                    if( !json_validate( $value ) )
                    {
                        throw new Error400( sprintf( 'The %s parameter is not valid JSON.' , $key ) ) ;
                    }

                    $params[ $key ] = urlencode( $value ) ;
                    $facets[ $key ] = json_decode( $value , true ) ;
                }
            }
        }
    }
}

