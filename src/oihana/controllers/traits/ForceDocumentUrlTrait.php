<?php

namespace oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;

use function oihana\core\arrays\isAssociative;

/**
 * Adds a computed `url` property to documents returned by a controller.
 *
 * This trait lets a controller force a canonical URL on a single document or on every
 * document of a collection, typically within the `get()` and `list()` methods. The URL
 * of a collection item is built by appending the document primary key to a base URL.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ForceDocumentUrlTrait
{
    /**
     * The default document primary key used to build per-document URLs.
     * @var string|null
     */
    public ?string $documentKey = ControllerParam::ID ;

    /**
     * Indicates if the controller force an url over the resources in the get() and list() methods.
     * @var bool
     */
    public bool $forceUrl = false ;

    /**
     * Appends a url property on the passed-in document reference.
     *
     * The property is set in-place: on the array (when associative) or on the object,
     * then the document is returned for convenience.
     *
     * @param object|array|null $document     The document to decorate, passed by reference.
     * @param string|null       $url          The URL value to assign to the document.
     * @param string            $propertyName The name of the property to set (defaults to {@see ControllerParam::URL}).
     *
     * @return object|array|null The same document reference, decorated with the url property.
     */
    protected function forceDocumentUrl( null|object|array &$document , ?string $url , string $propertyName = ControllerParam::URL ) :object|array|null
    {
        if( is_array( $document ) && isAssociative( $document ) )
        {
            $document[ $propertyName ] = $url ;
        }
        else if( is_object( $document ) )
        {
            $document->{ $propertyName } = $url ;
        }

        return $document ;
    }

    /**
     * Appends a url property on every document of the passed-in collection.
     *
     * For each document, the URL is built by concatenating the base `$url`, a slash separator
     * and the document primary key value. Documents that do not expose the key are left untouched.
     *
     * @param array|null  $documents    The collection of documents to decorate, passed by reference.
     * @param string|null $url          The base URL used to generate the URL of all documents.
     * @param ?string     $key          The optional document primary key to use; falls back to {@see $documentKey}.
     * @param string      $propertyName The name of the property to set (defaults to {@see ControllerParam::URL}).
     *
     * @return void
     */
    protected function forceDocumentsUrl( null|array &$documents , ?string $url , ?string $key = null , string $propertyName = ControllerParam::URL ) :void
    {
        if( is_array( $documents ) && count( $documents ) > 0 )
        {
            foreach( $documents as &$document )
            {
                if( is_array( $document ) && array_key_exists( $key ?? $this->documentKey , $document ) )
                {
                    $document[ $propertyName ] = $url . Char::SLASH . $document[ $key ?? $this->documentKey ] ;
                }
                else if( is_object( $document ) && property_exists( $document , $key ?? $this->documentKey ) )
                {
                    $document->{ $propertyName } = $url . Char::SLASH . $document->{ $key ?? $this->documentKey } ;
                }
            }
        }
    }

    /**
     * Initializes the document key and the force-url flag from an array.
     *
     * @param array $init Initialization array, read from the {@see ControllerParam::DOCUMENT_KEY} and {@see ControllerParam::FORCE_URL} keys.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializeForceUrl( array $init = [] ):static
    {
        $this->documentKey = $init[ ControllerParam::DOCUMENT_KEY ] ?? $this->documentKey ;
        $this->forceUrl    = $init[ ControllerParam::FORCE_URL    ] ?? $this->forceUrl ;
        return $this ;
    }
}