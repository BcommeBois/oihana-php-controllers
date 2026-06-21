<?php

namespace oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;

use function oihana\core\arrays\isAssociative;

trait ForceDocumentUrlTrait
{
    /**
     * The default document primary key.
     * @var string|null
     */
    public ?string $documentKey = ControllerParam::ID ;

    /**
     * Indicates if the controller force an url over the resources in the get() and list() methods.
     * @var bool
     */
    public bool $forceUrl = false ;

    /**
     * Append an url property on the passed-in document reference.
     * @param object|array|null $document
     * @param string|null $url
     * @param string $propertyName
     * @return object|array|null
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
     * Append an url property on the passed-in document reference.
     * @param array|null $documents The documents to append.
     * @param string|null $url The base url to use to generates the url of all documents.
     * @param ?string $key The optional document primary key to use to generates the url of all documents.
     * @param string $propertyName
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
     * Initialize the owner definition.
     * @param array $init
     * @return static
     */
    public function initializeForceUrl( array $init = [] ):static
    {
        $this->documentKey = $init[ ControllerParam::DOCUMENT_KEY ] ?? $this->documentKey ;
        $this->forceUrl    = $init[ ControllerParam::FORCE_URL    ] ?? $this->forceUrl ;
        return $this ;
    }
}