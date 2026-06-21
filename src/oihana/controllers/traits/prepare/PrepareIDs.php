<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

trait PrepareIDs
{
    /**
     * The default ids representation, a string list with comma separator or an array.
     * @var null|string|array
     */
    public null|string|array $ids = null ;

    /**
     * Initialize all skins properties with an associative array definition.
     * @param array $init
     * @return void
     */
    protected function initializeIDs( array $init = [] ):void
    {
        $this->ids = $init[ ControllerParam::IDS ] ?? null ;
    }

    /**
     * Prepares the identifier list representation (by default ids).
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @param string|null $name
     * @return string|null
     */
    protected function preparedIDs( ?Request $request , array $args = [] , ?array &$params = [] , ?string $name = ControllerParam::IDS ) :?string
    {
        $values = $args[ $name ] ?? $this->{ $name } ?? null ;

        if( is_array( $values ) )
        {
            $values = implode( Char::COMMA , $values ) ;
        }

        $register = false ;

        if ( isset( $request ) )
        {
            $param = getQueryParam( $request , $name ) ; // get only the query param (not body)
            if( is_string( $param ) )
            {
                $register = true ;
                $values = $param ;
            }
        }

        if( $register )
        {
            $params[ $name ] = $values ;
        }

        return $values ;
    }
}