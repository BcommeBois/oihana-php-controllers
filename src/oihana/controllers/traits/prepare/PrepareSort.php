<?php

namespace oihana\controllers\traits\prepare;

use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\ControllerParam;
use oihana\traits\SortDefaultTrait;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the `sort` parameter of a controller request.
 *
 * This trait resolves the sorting expression from the route arguments and lets the request
 * query string override it. When the request supplies a value it is stored in the parameter
 * bag; otherwise the method falls back to the explicit `$default` and then to the
 * `$sortDefault` provided by {@see SortDefaultTrait}.
 *
 * Only the query string is inspected (the request body is ignored).
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareSort
{
    use SortDefaultTrait ;

    /**
     * Prepares the sorting parameter based on the given request and arguments.
     * @param Request|null $request The request object, which may contain a sorting parameter.
     * @param array $args An associative array of arguments, which may include a predefined sorting value.
     * @param array|null &$params A reference to an array where the resolved sorting parameter will be stored.
     * @param string|null $default The fallback sorting expression used when neither the arguments nor the request provide one.
     * @param string $name The name of the sort parameter to read and store (defaults to {@see ControllerParam::SORT}).
     * @return string|null The resolved sorting parameter or the default sorting value if none is provided.
     */
    protected function prepareSort( ?Request $request , array $args = [] , ?array &$params = null , ?string $default = null , string $name = ControllerParam::SORT ) :?string
    {
        $sort = $args[ $name ] ?? null ;
        if( isset( $request ) )
        {
            $value = getQueryParam( $request , $name ); // query param only (not body param)
            if( isset( $value ) )
            {
                $params[ $name ] = $sort = $value ;
            }
        }
        return $sort ?? $default ?? $this->sortDefault ;
    }
}