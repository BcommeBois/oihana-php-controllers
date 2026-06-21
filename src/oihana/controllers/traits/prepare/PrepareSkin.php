<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\enums\Skin;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getQueryParam;

/**
 * Provides utilities to manage and prepare "skins" for controllers.
 * A "skin" represents a visual or behavioral variant of a controller's output.
 *
 * This trait handles:
 * - Default skin
 * - Method-specific skin definitions
 * - Enumeration of all available skins
 * - Validation and preparation of skins from requests or initialization arrays
 */
trait PrepareSkin
{
    /**
     * The default skin for the controller.
     *
     * This skin is used when no specific skin is requested or defined.
     *
     * @var string|null
     */
    public ?string $skinDefault = null ;

    /**
     * Method-specific skin definitions.
     *
     * Array format: [ 'methodName' => 'skinName', ... ]
     * Overrides the default skin for specific controller methods.
     *
     * @var array<string,string>|null
     */
    public ?array $skinMethods = null ;

    /**
     * List of all available skins for the controller.
     *
     * Used to validate requested skins.
     *
     * @var array<string>|null
     */
    public ?array $skins = null ;

    /**
     * Initialize the skin-related properties from an associative array.
     *
     * Example:
     * ```php
     * $this->initializeSkins([
     *     PrepareSkin::SKIN_DEFAULT => 'default',
     *     PrepareSkin::SKIN_METHODS => ['edit' => 'editor'],
     *     PrepareSkin::SKINS        => ['default', 'editor', 'compact']
     * ]);
     * ```
     *
     * @param array<string,mixed> $init Associative array of skin definitions.
     * @return static Returns the current instance for method chaining.
     */
    protected function initializeSkins( array $init = [] ) :static
    {
        $this->skinDefault = $init[ ControllerParam::SKIN_DEFAULT ] ?? $this->skinDefault ;
        $this->skinMethods = $init[ ControllerParam::SKIN_METHODS ] ?? $this->skinMethods ;
        $this->skins       = $init[ ControllerParam::SKINS ] ?? $this->skins ;
        return $this ;
    }

    /**
     * Validate a given skin.
     *
     * Checks if the skin is a string and exists in the defined skins list.
     * Converts the skin name to lowercase before validation.
     *
     * @param string|null $skin Skin name to validate.
     * @return bool True if the skin exists in the available skins list; false otherwise.
     */
    protected function isValidSkin( ?string $skin ) :bool
    {
        $check = is_string( $skin ) ;
        if( is_array( $this->skins ) )
        {
            $check = $check && in_array( strtolower( $skin ) , $this->skins ) ;
        }
        return $check ;
    }

    /**
     * Prepare and determine the effective skin for a request or method.
     *
     * This method considers:
     * - Initialization array
     * - Method-specific skin overrides
     * - Query parameter in the request
     * - Default skin fallback
     * - Validation against available skins
     *
     * Example:
     * ```php
     * $skin = $this->prepareSkin($request, ['skinDefault' => 'main'], $params, 'edit');
     * ```
     *
     * @param Request|null $request Optional HTTP request to read query parameters from.
     * @param array<string,mixed> $init Optional initialization array.
     * @param array<string,mixed>|null $params Reference to an array where the chosen skin will be stored.
     * @param string|null $method Optional controller method name to apply method-specific skin.
     * @return string|null Returns the prepared skin name in lowercase, or null if invalid or "main".
     */
    protected function prepareSkin( ?Request $request , array $init = [] , ?array &$params = null , ?string $method = null ) :?string
    {
        $skin = $init[ ControllerParam::SKIN ] ?? $this->skinDefault ;

        if( is_array( $this->skinMethods ) && is_string( $method ) )
        {
            $skin = $this->skinMethods[ $method ] ?? $this->skinDefault ;
        }

        $register = false ;

        if ( isset( $request ) )
        {
            $value = getQueryParam( $request , ControllerParam::SKIN ) ; // get only the query param (not body)
            if( $this->isValidSkin( $value ) )
            {
                $skin     = $value ;
                $register = true ;
            }
        }

        if( !empty( $skin ) )
        {
            $skin = strtolower( $skin ) ;
        }

        if( $register )
        {
            $params[ ControllerParam::SKIN ] = $skin ;
        }

        if( $skin === Skin::MAIN || !$this->isValidSkin( $skin ) )
        {
            $skin = null ;
        }

        return $skin ;
    }
}