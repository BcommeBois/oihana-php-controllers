<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the identifier list of a controller request.
 *
 * This trait normalizes a list of identifiers into a comma-separated string. The value
 * resolves from the route arguments or the matching controller property (arrays are
 * imploded with {@see Char::COMMA}); a string query parameter overrides it and, when
 * present, the value is recorded in the parameter bag. The parameter name defaults to
 * {@see ControllerParam::IDS} but can be customized.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareIDs
{
    /**
     * The default ids representation, a string list with comma separator or an array.
     * @var null|string|array
     */
    public null|string|array $ids = null ;

    /**
     * Initialize all skins properties with an associative array definition.
     * @param array $init The configuration array, optionally carrying a {@see ControllerParam::IDS} entry.
     * @return void
     */
    protected function initializeIDs( array $init = [] ):void
    {
        $this->ids = $init[ ControllerParam::IDS ] ?? null ;
    }

    /**
     * Prepares the identifier list representation (by default ids).
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial identifier list.
     * @param array|null   $params  A reference to the parameter bag updated in place with the comma-separated identifiers.
     * @param string|null  $name    The name of the identifier parameter to read and store (defaults to {@see ControllerParam::IDS}).
     * @return string|null The resolved comma-separated identifier list, or null when none is provided.
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