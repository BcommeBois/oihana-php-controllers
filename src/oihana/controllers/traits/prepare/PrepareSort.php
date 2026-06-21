<?php

namespace oihana\controllers\traits\prepare;

use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\ControllerParam;
use oihana\traits\SortDefaultTrait;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareSort
{
    use SortDefaultTrait ;

    /**
     * Prepares the sorting parameter based on the given request and arguments.
     * @param Request|null $request The request object, which may contain a sorting parameter.
     * @param array $args An associative array of arguments, which may include a predefined sorting value.
     * @param array|null &$params A reference to an array where the resolved sorting parameter will be stored.
     * @param string|null $default
     * @param string $name
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