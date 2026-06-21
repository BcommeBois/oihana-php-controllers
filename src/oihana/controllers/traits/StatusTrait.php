<?php

namespace oihana\controllers\traits ;

use oihana\enums\Char;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;
use oihana\enums\Output;
use oihana\files\enums\FileMimeType;
use oihana\logging\LoggerTrait;

use Slim\Psr7\Factory\StreamFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Provides standardized methods for outputting HTTP status messages and JSON responses.
 *
 * This trait offers:
 * - fail(): to generate structured error responses with logging support.
 * - status(): to generate generic status messages.
 * - success(): to generate success JSON responses, optionally including metadata like count, owner, URL, pagination, etc.
 *
 * Relies on:
 * - BaseUrlTrait: for generating current paths.
 * - JsonTrait: for sending JSON responses.
 * - LoggerTrait: for optional logging of error messages.
 *
 * Usage example:
 * ```php
 * return $this->fail($response, 406, 'Invalid data', ['firstName' => 'required']);
 * return $this->status($response, 'custom message', 200);
 * return $this->success($request, $response, $data, [Output::COUNT => count($data)]);
 * ```
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait StatusTrait
{
    use BaseUrlTrait ,
        CborTrait    ,
        JsonTrait    ,
        LoggerTrait  ;

    /**
     * Generates a structured error response with an HTTP status code and optional detailed messages.
     *
     * Automatically logs the error if logging is enabled.
     *
     * @param ?Request        $request  Optional PSR-7 Request object.
     * @param ?Response       $response The PSR-7 Response object.
     * @param int|string|null $code     The HTTP status code (default: 400).
     * @param ?string         $details  Optional detailed error message to override default description.
     * @param array           $options  Optional array of additional data to include (e.g., errors).
     * @param ?string         $accept   The header accepted by the client : 'application/cbor' or by default 'application/json'
     *
     * @return ?Response Returns a PSR-7 Response object with JSON content or null if $response is not provided.
     *
     * @example
     * ```php
     * return $this->fail(
     *     $response,
     *     406,
     *     'fields validation failed',
     *     [
     *         'firstName' => 'firstName is required',
     *         'lastName'  => 'lastName must be a string'
     *     ]
     * );
     * ```
     */
    public function fail
    (
        ?Request        $request        ,
        ?Response       $response       ,
        string|int|null $code    = 400  ,
        ?string         $details = null ,
        array           $options = []   ,
        ?string         $accept  = null ,
    )
    :?Response
    {
        $code       = (int) ( HttpStatusCode::includes( (int) $code ) ? $code : HttpStatusCode::DEFAULT ) ;
        $message    = HttpStatusCode::getDescription( $code ) ;
        $hasDetails = is_string( $details ) && $details != Char::EMPTY ;

        if( $this->loggable )
        {
            $log = implode( Char::SPACE , [ static::class , $code , Char::PIPE , $message ] ) ;
            if( $hasDetails )
            {
                $log .= Char::SPACE . Char::PIPE . Char::SPACE . $details ;
            }
            $this->logger->error( $log ) ;
        }

        if( $hasDetails )
        {
            $options[ Output::DETAILS ] = $details ;
        }

        return $this->status
        (
            $request  ,
            $response ,
            $message  ,
            $code     ,
            count($options) > 0 ? $options : null ,
            $accept
        ) ;
    }

    /**
     * Return a response in the format accepted by the client : JSON by default or CBOR.
     *
     * Checks the `Accept` header in the request to determine the preferred format.
     *
     * @param  Response  $response  PSR-7 Response object to write to.
     * @param  mixed     $data      Data to send in the response.
     * @param  int       $status    HTTP status code (default: 200).
     * @param ?string    $accept    The header accepted by the client : 'application/cbor' or by default 'application/json'
     *
     * @return Response The response encoded as CBOR or JSON according to the negotiated format.
     *
     * @see FileMimeType::JSON
     * @see FileMimeType::CBOR
     * @see FileMimeType::CBOR_SEQ
     */
    public function response
    (
        Response  $response ,
        mixed     $data     = null,
        int       $status   = 200 ,
        ?string   $accept   = null ,
    )
    : Response
    {
        return match( $accept )
        {
            FileMimeType::CBOR , FileMimeType::CBOR_SEQ => $this->cborResponse( $response , $data , $status ) ,
            default                                     => $this->jsonResponse( $response , $data , $status )
        };
    }

    /**
     * Outputs a generic HTTP status message in a JSON response.
     *
     * @param ?Request        $request  Optional PSR-7 Request object.
     * @param ?Response       $response PSR-7 Response object to send output.
     * @param mixed           $message  The message content.
     * @param int|string|null $code     The HTTP status code (default: 200).
     * @param ?array          $options  Optional array of additional output properties.
     * @param ?string         $accept   The header accepted by the client : 'application/cbor' or by default 'application/json'
     *
     * @return ?Response Returns a PSR-7 Response object with JSON content or null if $response is not provided.
     *
     * @example
     * ```php
     * return $this->status($response, 'bad request', 405);
     * ```
     */
    public function status
    (
        ?Request        $request                ,
        ?Response       $response               ,
        mixed           $message  = Char::EMPTY ,
        int|string|null $code     = 200         ,
        ?array          $options = null         ,
        ?string         $accept  = null         ,
    )
    :?Response
    {
        if( isset( $response ) )
        {
            $status = (int) ( $code ?? 200 ) ;
            $type   = HttpStatusCode::getType( $code ) ;

            if( isset( $type ) )
            {
                $output[ Output::STATUS ] = $type ;
            }

            if( isset( $code ) )
            {
                $output[ Output::CODE ] = (int) $code ;
            }

            $output[ Output::MESSAGE ] = $message ;

            if( is_array( $options ) && count( $options ) > 0 )
            {
                $output = [ ...$output , ...$options ] ;
            }

            $acceptHeader = $accept ?? $request?->getHeaderLine(HttpHeader::ACCEPT ) ?? null ;

            return $this->response( $response , $output , $status , $acceptHeader ) ;
        }
        return null ;
    }


    /**
     * Outputs a success message with optional JSON metadata.
     *
     * If $response is null, returns the $data directly.
     * Supports optional initialization properties like count, limit, offset, owner, URL, status, total, position, options.
     *
     * @param ?Request  $request  Optional PSR-7 Request object.
     * @param ?Response $response Optional PSR-7 Response object.
     * @param mixed     $data     The main payload or data to return.
     * @param ?array    $init     Optional associative array with keys:
     *                            - count (int): Number of elements
     *                            - limit (int): Pagination limit
     *                            - offset (int): Pagination offset
     *                            - params (array): Parameters for getCurrentPath()
     *                            - status (int): HTTP status code
     *                            - total (int): Total elements
     *                            - url (string): URL to include in response
     *                            - owner (array|object): Owner reference
     *                            - options (array): Additional properties
     *                            - position (int): Optional position in list
     * @param ?string   $accept   The header accepted by the client : 'application/cbor' or by default 'application/json'
     *
     * @return mixed Returns a PSR-7 Response object with JSON if $response is provided, otherwise returns $data directly.
     *
     * @example
     * ```php
     * return $this->success(
     *     $request,
     *     $response,
     *     $data,
     *     [Output::COUNT => count($data), Output::PARAMS => $request->getQueryParams()]
     * );
     * ```
     */
    public function success
    (
        ?Request  $request         ,
        ?Response $response        ,
        mixed     $data     = null ,
        ?array    $init     = null ,
        ?string   $accept   = null ,
    )
    :mixed
    {
        if( isset( $response ) )
        {
            $count    = $init[ Output::COUNT    ] ?? null ;
            $limit    = $init[ Output::LIMIT    ] ?? null ;
            $offset   = $init[ Output::OFFSET   ] ?? null ;
            $params   = $init[ Output::PARAMS   ] ?? [] ;
            $status   = $init[ Output::STATUS   ] ?? 200 ;
            $url      = $init[ Output::URL      ] ?? $this->getCurrentPath( $request , $params ) ;
            $owner    = $init[ Output::OWNER    ] ?? null  ;
            $options  = $init[ Output::OPTIONS  ] ?? null  ;
            $position = $init[ Output::POSITION ] ?? null  ;
            $total    = $init[ Output::TOTAL    ] ?? null  ;

            $output = [ Output::STATUS => Output::SUCCESS ];

            if( is_string( $url ) && $url != Char::EMPTY )
            {
                $output[ Output::URL ] = $url;
            }

            if( is_int( $limit ) )
            {
                $output[ Output::LIMIT ] = $limit ;
            }

            if( is_int( $offset ) )
            {
                $output[ Output::OFFSET ] = $offset ;
            }

            if( is_int( $position ) && $position >= 0 )
            {
                $output[ Output::POSITION ] = $position ;
            }

            if( is_int( $count ) && $count >= 0 )
            {
                $output[ Output::COUNT ] = $count;
            }

            if( is_int( $total ) && $total >= 0 )
            {
                $output[ Output::TOTAL ] = $total ;
            }

            if( $owner )
            {
                $output[ Output::OWNER ] = $owner ;
            }

            if( is_array( $options ) && count( $options ) > 0 )
            {
                $output = [ ...$output , ...$options ] ;
            }

            $output[ Output::RESULT ] = $data ;

            $acceptHeader = $accept ?? $request?->getHeaderLine(HttpHeader::ACCEPT ) ?? null ;

            return $this->response( $response , $output , $status , $acceptHeader ) ;
        }

        return $data ;
    }

    /**
     * Same as {@see self::success()} but guarantees a fresh response body
     * stream before writing the envelope.
     *
     * Use this **only** when an upstream actor (typically a sub-controller
     * called from the current controller method) may have already written
     * into the shared PSR-7 body stream. Calling the plain {@see success()}
     * in that case would concatenate two JSON envelopes — invalid JSON for
     * any strict parser (NextJS RSC, modern fetch, etc.).
     *
     * Implementation: swaps the response body for an empty stream via
     * {@see self::withFreshBody()}, then delegates to {@see success()}.
     * Whatever was previously written is discarded; the resulting body
     * contains exactly one envelope.
     *
     * @param ?Request  $request  Optional PSR-7 Request object.
     * @param ?Response $response Optional PSR-7 Response object.
     * @param mixed     $data     The main payload or data to return.
     * @param ?array    $init     Same keys as {@see success()}.
     * @param ?string   $accept   The header accepted by the client.
     *
     * @return mixed Same return contract as {@see success()}.
     *
     * @example
     * ```php
     * // POST /users where dispatchAutoInvitation() writes into the shared body
     * public function post( ?Request $request , ?Response $response , array $args , array $init ) :mixed
     * {
     *     $result = parent::post( $request , $response , $args , $init ) ;
     *     $this->dispatchAutoInvitation( $request , $response , $userKey ) ;
     *
     *     return $this->successWithNewBody
     *     (
     *         $request ,
     *         $result  ,
     *         $this->refetchHydratedUser( $userKey )
     *     ) ;
     * }
     * ```
     *
     * @see success() Plain variant when the body has not been touched.
     */
    public function successWithNewBody
    (
        ?Request  $request         ,
        ?Response $response        ,
        mixed     $data     = null ,
        ?array    $init     = null ,
        ?string   $accept   = null ,
    )
    :mixed
    {
        return $this->success
        (
            $request                          ,
            $this->withFreshBody( $response ) ,
            $data                             ,
            $init                             ,
            $accept
        ) ;
    }

    /**
     * Returns the same response with a fresh, empty body stream.
     * Use to discard whatever an upstream actor (sub-controller, middleware)
     * may have already written, then chain into any other response helper.
     *
     * @param ?Response $response Optional PSR-7 Response object.
     *
     * @return ?Response The same response with a fresh empty body,
     *                   or null if `$response` was null.
     *
     * @example
     * ```php
     * return $this->fail( $request , $this->withFreshBody( $response ) , 502 , 'zitadel_sync_failed' ) ;
     * ```
     */
    public function withFreshBody( ?Response $response ) :?Response
    {
        return $response?->withBody( new StreamFactory()->createStream( Char::EMPTY ) ) ;
    }
}