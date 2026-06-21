<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\LanguagesTrait;

use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Prepares the `lang` parameter of a controller request.
 *
 * This trait resolves the requested language from the {@see ControllerParam::LANG} query
 * parameter against the supported `$languages` set provided by {@see LanguagesTrait}. A
 * recognized language is lowercased and recorded in the parameter bag, while the special
 * {@see ControllerParam::ALL} value resets it to null (no language filtering).
 *
 * Only the query string is inspected (the request body is ignored).
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareLang
{
    use LanguagesTrait ;

    /**
     * Prepares and returns the `lang` value.
     *
     * @param Request|null $request The incoming PSR-7 server request, or null when no request context is available.
     * @param array        $args    The route/controller arguments that may carry an initial `lang` value.
     * @param array|null   $params  A reference to the parameter bag updated in place with the resolved language.
     * @return string|null The resolved language code, or null when language filtering is disabled.
     */
    protected function prepareLang( ?Request $request , array $args = [] , ?array &$params = null ) :?string
    {
        $lang = $args[ ControllerParam::LANG ] ?? null ;
        if( isset( $request ) )
        {
            $value = getQueryParam( $request , ControllerParam::LANG ) ; // query param only (not body)
            if( !empty( $value ) )
            {
                if( in_array( $value , $this->languages ) )
                {
                    $lang = strtolower( $value ) ;
                }
                else if( strtolower( $value ) == ControllerParam::ALL )
                {
                    $lang = null ;
                }
            }

            if( !empty( $lang ) && $params )
            {
                $params[ ControllerParam::LANG ] = $lang ;
            }
        }
        return $lang ;
    }
}