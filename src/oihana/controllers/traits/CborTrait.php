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
 * Provides utility methods for managing Cbor encoding options and creating
 * standardized JSON HTTP responses within controllers.
 */
trait CborTrait
{
    /**
     * Temporary serialization options.
     * (ex: ArrayOption::REDUCE, custom schema flags, etc.)
     * @var array
     */
    public array $cborSerializeOptions = [ ArrayOption::REDUCE => true ] ;

    /**
     * Initialize the internal $cborSerializeOptions property.
     * @param array $init
     * @param ContainerInterface|null $container
     * @return static
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * Return a cbor response
     * @param Response $response
     * @param mixed $data
     * @param int $status
     * @return Response
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