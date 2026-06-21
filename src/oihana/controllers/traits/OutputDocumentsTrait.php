<?php

namespace oihana\controllers\traits;

use oihana\enums\Output;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Provides a standardized way to output documents from controllers.
 *
 * This trait offers methods to format documents responses, generate
 * URLs for documents, and handle optional response wrapping with PSR-7
 * response objects. It relies on BaseUrlTrait for URL generation and
 * StatusTrait for response formatting.
 *
 * Usage example:
 * ```php
 * $documents = $this->outputDocuments($request, $response, $data, ['page' => 1]);
 * ```
 */
trait OutputDocumentsTrait
{
    use BaseUrlTrait ,
        StatusTrait ;

    /**
     * Generates a standardized response for a list of documents.
     *
     * Wraps the given documents in a success response, including count, options, and a document URL.
     *
     * @param Request|null  $request   Optional PSR-7 request object.
     * @param Response|null $response  Optional PSR-7 response object.
     * @param array|null    $documents Optional array of documents to include in the response.
     * @param array         $params    Optional parameters used for URL generation.
     * @param array|null    $options   Optional additional options to include in the response.
     *
     * @return object|null Returns a success-wrapped object if $response is provided; otherwise null.
     */
    protected function documentsResponse( ?Request $request = null, ?Response $response = null , ?array $documents = null , array $params = [] , ?array $options = null ) :?object
    {
        return $this->success( $request , $response, $documents ,
        [
            Output::COUNT   => is_array( $documents ) ? count( $documents ) : null ,
            Output::OPTIONS => $options ,
            Output::URL     => $this->getDocumentUrl( $request , $params )
        ]);
    }

    /**
     * Returns the URL associated with the documents response.
     *
     * By default, this method uses BaseUrlTrait::getCurrentPath() but can
     * be overridden in the controller to provide a custom URL generation logic.
     *
     * @param Request|null $request Optional PSR-7 request object.
     * @param array        $params  Optional parameters to append to the URL.
     *
     * @return string The generated document URL.
     */
    protected function getDocumentUrl( ?Request $request = null , array $params = [] ) :string
    {
        return $this->getCurrentPath( $request , $params ) ;
    }

    /**
     * Outputs a list of documents, optionally wrapping them in a response object.
     *
     * If a PSR-7 Response object is provided, the documents are wrapped using
     * `documentsResponse()`. Otherwise, the raw array of documents is returned.
     *
     * @param Request|null  $request   Optional PSR-7 request object.
     * @param Response|null $response  Optional PSR-7 response object.
     * @param array|null    $documents Optional array of documents to output.
     * @param array         $params    Optional parameters for URL generation.
     * @param array|null    $options   Optional additional response options.
     *
     * @return array|object|null Returns a wrapped response object if $response is provided,
     *                           otherwise the raw documents array or null.
     */
    protected function outputDocuments
    (
        ?Request  $request   = null,
        ?Response $response  = null ,
        ?array    $documents = null ,
        array     $params    = []   ,
        ?array    $options   = null
    )
    : array|object|null
    {
        if( $response )
        {
            return $this->documentsResponse
            (
                $request ,
                $response ,
                $documents ,
                array_filter( $params , fn( $value ) => !is_null( $value ) ) ,
                $options
            ) ;
        }
        return $documents ;
    }
}