<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;
use oihana\core\options\ArrayOption;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;
use oihana\enums\JsonParam;
use oihana\files\enums\FileMimeType;

use oihana\reflect\utils\JsonSerializer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;

use function oihana\core\json\isValidJsonEncodeFlags;

/**
 * Provides utility methods for managing JSON encoding options and creating
 * standardized JSON HTTP responses within controllers.
 *
 * This trait is designed to:
 * - Initialize and manage JSON encoding flags.
 * - Build PSR-7 JSON responses with proper headers.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait JsonTrait
{
    /**
     * The default JSON encoding flags used in the controller (bitmask of `JSON_*` constants).
     *
     * @var int
     */
    public int $jsonOptions = JsonParam::JSON_NONE ;

    /**
     * Temporary serialization options passed to the {@see JsonSerializer}.
     * (ex: {@see ArrayOption::REDUCE}, custom schema flags, etc.)
     *
     * @var array
     */
    public array $jsonSerializeOptions = [ ArrayOption::REDUCE => true ] ;

    /**
     * Initializes the internal `$jsonOptions` and `$jsonSerializeOptions` properties.
     *
     * The JSON encode flags and the serializer options are taken from `$init` when present;
     * otherwise they are looked up in the DI container under the matching {@see ControllerParam}
     * keys. Invalid encode flags fall back to {@see JsonParam::JSON_NONE}.
     *
     * @param array                   $init      Optional initialization array (e.g. `['jsonOptions' => ..., 'jsonSerializeOptions' => [ ... ]]`).
     * @param ContainerInterface|null $container Optional PSR-11 container used to resolve the JSON options.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws NotFoundExceptionInterface If no entry was found in the container for the given identifier.
     */
    public function initializeJsonOptions
    (
        array $init = [] ,
        ?ContainerInterface $container = null
    )
    :static
    {
        // --- JSON encode flags ---

        $flags = $init[ ControllerParam::JSON_OPTIONS ] ?? JsonParam::JSON_NONE ;

        if( $flags == null && $container instanceof ContainerInterface && $container->has( ControllerParam::JSON_OPTIONS ) )
        {
            $flags = (int) $container->get( ControllerParam::JSON_OPTIONS ) ;
        }

        $this->jsonOptions = isValidJsonEncodeFlags( $flags ) ? $flags : JsonParam::JSON_NONE ;

        // --- JsonSerializer temporary options ---

        $options = $init[ ControllerParam::JSON_SERIALIZE_OPTIONS ] ?? $this->jsonSerializeOptions ;

        if
        (
            empty( $options ) &&
            $container instanceof ContainerInterface &&
            $container->has( ControllerParam::JSON_SERIALIZE_OPTIONS )
        )
        {
            $options = (array) $container->get( ControllerParam::JSON_SERIALIZE_OPTIONS ) ;
        }

        $this->jsonSerializeOptions = is_array( $options ) ? $options : $this->jsonSerializeOptions ;

        return $this ;
    }

    /**
     * Builds a PSR-7 JSON response.
     *
     * The payload is encoded with {@see JsonSerializer::encode()} using the configured
     * {@see self::$jsonOptions} encode flags and {@see self::$jsonSerializeOptions}, then
     * written to the response body with the JSON `Content-Type` header.
     *
     * @param Response $response The PSR-7 Response object to write into.
     * @param mixed    $data     The data to encode as JSON (defaults to `null`).
     * @param int      $status   The HTTP status code to set on the response (defaults to {@see HttpStatusCode::OK}).
     *
     * @return Response The response carrying the JSON-encoded body and header.
     *
     * @example
     * ```php
     * class UserController extends Controller
     * {
     *     use JsonTrait ;
     *
     *     public function show( Request $request , Response $response ) : Response
     *     {
     *         return $this->jsonResponse( $response , [ 'id' => 42 , 'name' => 'Alice' ] ) ;
     *     }
     * }
     * ```
     */
    public function jsonResponse
    (
        Response $response                      ,
        mixed    $data     = null               ,
        int      $status   = HttpStatusCode::OK
    )
    : Response
    {
        $response->getBody()->write
        (
            JsonSerializer::encode
            (
                $data ,
                $this->jsonOptions ,
                $this->jsonSerializeOptions
            )
        ) ;

        return $response
            ->withStatus( $status )
            ->withHeader( HttpHeader::CONTENT_TYPE , FileMimeType::JSON );
    }
}