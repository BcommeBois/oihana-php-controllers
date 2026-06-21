<?php

namespace oihana\controllers\traits;

use Exception;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Psr7\Factory\StreamFactory;

use oihana\controllers\enums\FileResponseOption;

use function oihana\controllers\helpers\applyContentHeaders;
use function oihana\files\assertFile;

/**
 * Serves a file as a PSR-7 download response.
 *
 * The single helper validates the file, then streams it lazily so large files are never
 * loaded entirely into memory. A missing or unreadable file is reported as a `500`
 * response rather than leaking native warnings, and content headers (`Content-Type`,
 * `Content-Length`, `Content-Disposition`) are opt-in via the `$options` array.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait FileTrait
{
    use StatusTrait ;

    /**
     * Streams a file as a PSR-7 download response.
     *
     * The file is validated with {@see assertFile()} first; a missing or
     * unreadable file is reported as a `500` response (see the `catch` below) rather
     * than leaking warnings. Optional content headers are toggled via `$options`.
     *
     * @param ?Request $request  Optional PSR-7 Request object (used to build the failure response).
     * @param Response $response The PSR-7 Response object to write the file into.
     * @param string   $file     Absolute path of the file to send.
     * @param array    $options  Optional header switches, keyed by {@see FileResponseOption}:
     *                           - `useContentType`        (bool)   add a `Content-Type` header (detected MIME type).
     *                           - `useContentLength`      (bool)   add a `Content-Length` header (file size).
     *                           - `useContentDisposition` (bool)   add a `Content-Disposition` header.
     *                           - `contentDisposition`    (string) the `Content-Disposition` value to use.
     *
     * @return Response The response carrying the file body, or a `500` failure response on error.
     *
     * @example
     * ```php
     * class DownloadController extends Controller
     * {
     *     use FileTrait ;
     *
     *     public function invoice( Request $request , Response $response ) : Response
     *     {
     *         return $this->fileResponse( $request , $response , '/var/invoices/2026-06.pdf' , [
     *             FileResponseOption::USE_CONTENT_TYPE   => true ,
     *             FileResponseOption::USE_CONTENT_LENGTH => true ,
     *         ] ) ;
     *     }
     * }
     * ```
     */
    public function fileResponse
    (
        ?Request $request ,
        Response $response ,
        string $file ,
        array $options = []
    )
    : Response
    {
        try
        {
            assertFile( $file ) ; // throws FileException (extends Exception) on a missing/unreadable file

            // headers off by default here: the caller opts in via $options
            $response = applyContentHeaders( $response , $file , null , $options , defaultOn: false ) ;

            // streamed (lazy read at emit time) so large files are not loaded into memory
            return $response->withBody( new StreamFactory()->createStreamFromFile( $file ) ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response ,  500 , $e->getMessage() ) ;
        }
    }
}
