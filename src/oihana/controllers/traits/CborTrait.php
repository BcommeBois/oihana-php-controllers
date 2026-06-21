<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;
use oihana\core\options\ArrayOption;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;
use oihana\files\enums\FileMimeType;

use oihana\reflect\utils\CborSerializer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Psr7\Factory\StreamFactory;

/**
 * Provides utility methods for managing CBOR serialization options and creating
 * standardized CBOR HTTP responses within controllers.
 *
 * This trait is designed to:
 * - Initialize and manage the CBOR serialization options (e.g. {@see ArrayOption::REDUCE}).
 * - Build PSR-7 CBOR responses with the proper `Content-Type` and `Content-Length` headers.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait CborTrait
{
    /**
     * Temporary serialization options passed to the {@see CborSerializer}.
     * (ex: {@see ArrayOption::REDUCE}, custom schema flags, etc.)
     *
     * @var array
     */
    public array $cborSerializeOptions = [ ArrayOption::REDUCE => true ] ;

    /**
     * Initializes the internal `$cborSerializeOptions` property.
     *
     * The options are taken from `$init[ControllerParam::CBOR_SERIALIZE_OPTIONS]` when present;
     * otherwise, if empty, they are looked up in the DI container under the same key.
     *
     * @param array                   $init      Optional initialization array (e.g. `['cborSerializeOptions' => [ ... ]]`).
     * @param ContainerInterface|null $container Optional PSR-11 container used to resolve the serialization options.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws NotFoundExceptionInterface If no entry was found in the container for the given identifier.
     */
    public function initializeCborOptions
    (
        array $init = [] ,
        ?ContainerInterface $container = null
    )
    :static
    {
        $options = $init[ ControllerParam::CBOR_SERIALIZE_OPTIONS ] ?? $this->cborSerializeOptions ;

        if
        (
            empty( $options ) &&
            $container instanceof ContainerInterface &&
            $container->has( ControllerParam::CBOR_SERIALIZE_OPTIONS )
        )
        {
            $options = (array) $container->get( ControllerParam::CBOR_SERIALIZE_OPTIONS ) ;
        }

        $this->cborSerializeOptions = is_array( $options ) ? $options : $this->cborSerializeOptions ;

        return $this ;
    }

    /**
     * Builds a PSR-7 CBOR response.
     *
     * The payload is encoded with {@see CborSerializer::encode()} using the configured
     * {@see self::$cborSerializeOptions}, any pending output buffer is cleared, and the
     * resulting binary is written to a fresh stream advertised with the CBOR MIME type.
     *
     * @param Response $response The PSR-7 Response object to write into.
     * @param mixed    $data     The data to encode as CBOR (defaults to `null`).
     * @param int      $status   The HTTP status code to set on the response (defaults to {@see HttpStatusCode::OK}).
     *
     * @return Response The response carrying the CBOR-encoded body and headers.
     *
     * @example
     * ```php
     * class FeedController extends Controller
     * {
     *     use CborTrait ;
     *
     *     public function index( Request $request , Response $response ) : Response
     *     {
     *         return $this->cborResponse( $response , [ 'hello' => 'world' ] ) ;
     *     }
     * }
     * ```
     */
    public function cborResponse
    (
        Response $response                      ,
        mixed    $data     = null               ,
        int      $status   = HttpStatusCode::OK
    )
    : Response
    {
        $data = CborSerializer::encode
        (
            $data ,
            $this->cborSerializeOptions
        ) ;

        if ( ob_get_length() > 0 )
        {
            ob_clean() ;
        }

        $stream = new StreamFactory()->createStream( $data ) ;

        return $response
            ->withBody( $stream )
            ->withHeader( HttpHeader::CONTENT_TYPE   , FileMimeType::CBOR )
            ->withHeader( HttpHeader::CONTENT_LENGTH , (string) strlen( $data ) )
            ->withStatus( $status ) ;
    }
}