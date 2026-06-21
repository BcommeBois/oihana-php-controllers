<?php

namespace oihana\controllers\traits;

use Exception;
use oihana\files\exceptions\DirectoryException;
use oihana\files\exceptions\FileException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use oihana\controllers\enums\FileResponseOption;
use oihana\files\openssl\OpenSSLFileEncryption;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\applyContentHeaders;

/**
 * Provides helpers to encrypt/decrypt files and stream them as PSR-7 download responses,
 * delegating the cryptography to a configured `oihana\files\openssl\OpenSSLFileEncryption`.
 *
 * The encryption instance is supplied through {@see self::initializeFileEncryption()} (from
 * an init array or a PSR-11 container), so the passphrase lives in the DI configuration and
 * never in this trait.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait FileEncryptionTrait
{
    use StatusTrait ;

    /**
     * The key used to initialize the file encryption instance from an array.
     */
    public const string FILE_ENCRYPTION = 'fileEncryption' ;

    /**
     * The file encryption helper (optional). When unset, the helpers below fail.
     *
     * @var OpenSSLFileEncryption|null
     */
    protected ?OpenSSLFileEncryption $fileEncryption = null ;

    /**
     * Decrypts a file and returns the path of the produced (clear) file.
     *
     * @param string $input Path of the encrypted file.
     * @param string|null $output Optional output path (defaults to `$input` without its `.enc` suffix).
     *
     * @return string The path of the decrypted file.
     *
     * @throws FileException If the input file is missing/unreadable or the decryption fails.
     */
    public function decryptFile( string $input , ?string $output = null ) : string
    {
        return $this->requireFileEncryption()->decrypt( $input , $output ) ;
    }

    /**
     * Decrypts a file and streams the clear content as a download response.
     *
     * @param ?Request $request  Optional PSR-7 Request object (used to build the failure response).
     * @param Response $response The PSR-7 Response object to write the file into.
     * @param string   $file     Path of the encrypted file.
     * @param array    $options  Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the decrypted file, or a `500` failure response on error.
     */
    public function decryptFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
    {
        try
        {
            $produced = $this->requireFileEncryption()->decrypt( $file ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 , $e->getMessage() ) ;
        }

        return $this->streamProducedFile( $response , $produced , $options ) ;
    }

    /**
     * Encrypts a file and returns the path of the produced (encrypted) file.
     *
     * @param string $input Path of the plaintext file.
     * @param string|null $output Optional output path (defaults to `$input` with a `.enc` suffix).
     *
     * @return string The path of the encrypted file.
     *
     * @throws DirectoryException If the output directory cannot be created or written.
     * @throws FileException If the input file is missing/unreadable or the encryption fails.
     */
    public function encryptFile( string $input , ?string $output = null ) : string
    {
        return $this->requireFileEncryption()->encrypt( $input , $output ) ;
    }

    /**
     * Encrypts a file and streams the encrypted content as a download response.
     *
     * @param ?Request $request  Optional PSR-7 Request object (used to build the failure response).
     * @param Response $response The PSR-7 Response object to write the file into.
     * @param string   $file     Path of the plaintext file.
     * @param array    $options  Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the encrypted file, or a `500` failure response on error.
     */
    public function encryptedFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
    {
        try
        {
            $produced = $this->requireFileEncryption()->encrypt( $file ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 , $e->getMessage() ) ;
        }

        return $this->streamProducedFile( $response , $produced , $options ) ;
    }

    /**
     * Initializes the internal file encryption helper.
     *
     * Priority order:
     * 1. `$init[FileEncryptionTrait::FILE_ENCRYPTION]`
     * 2. `$container->get(OpenSSLFileEncryption::class)` if available.
     *
     * @param array $init Optional initialization array.
     * @param ContainerInterface|null $container Optional PSR-11 container.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws NotFoundExceptionInterface If no entry was found in the container for the given identifier.
     */
    public function initializeFileEncryption( array $init = [] , ?ContainerInterface $container = null ) : static
    {
        $encryption = $init[ self::FILE_ENCRYPTION ] ?? null ;

        if( !$encryption instanceof OpenSSLFileEncryption && $container instanceof ContainerInterface && $container->has( OpenSSLFileEncryption::class ) )
        {
            $encryption = $container->get( OpenSSLFileEncryption::class ) ;
        }

        if( $encryption instanceof OpenSSLFileEncryption )
        {
            $this->fileEncryption = $encryption ;
        }

        return $this ;
    }

    /**
     * Returns the configured encryption helper or throws if none was provided.
     *
     * @return OpenSSLFileEncryption The configured file encryption helper.
     *
     * @throws RuntimeException If no encryption helper has been initialized.
     */
    private function requireFileEncryption() : OpenSSLFileEncryption
    {
        if( !$this->fileEncryption instanceof OpenSSLFileEncryption )
        {
            throw new RuntimeException( 'File encryption is not configured. Call initializeFileEncryption() first.' ) ;
        }
        return $this->fileEncryption ;
    }

    /**
     * Emits the download headers for a produced file, streams it into the response body,
     * then removes the temporary file.
     *
     * @param Response $response The PSR-7 Response object to write the file into.
     * @param string   $produced Path of the produced (encrypted or decrypted) file.
     * @param array    $options  Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the file body.
     */
    private function streamProducedFile( Response $response , string $produced , array $options = [] ) : Response
    {
        $response = applyContentHeaders( $response , $produced , null , $options , defaultOn: true ) ;

        $response->getBody()->write( file_get_contents( $produced ) ) ;

        @unlink( $produced ) ; // the content is already in the response body; drop the temp file

        return $response ;
    }
}
