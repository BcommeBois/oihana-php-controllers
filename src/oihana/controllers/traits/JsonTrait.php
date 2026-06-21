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
 */
trait JsonTrait
{
    /**
     * The default json options used in the controller.
     * @var int
     */
    public int $jsonOptions = JsonParam::JSON_NONE ;

    /**
     * Temporary serialization options passed to JsonSerializer.
     * (ex: ArrayOption::REDUCE, custom schema flags, etc.)
     * @var array
     */
    public array $jsonSerializeOptions = [ ArrayOption::REDUCE => true ] ;

    /**
     * Initialize the internal $jsonOptions property.
     * @param array $init
     * @param ContainerInterface|null $container
     * @return static
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * Return a JSON response
     * @param Response $response
     * @param mixed $data
     * @param int $status
     * @return Response
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