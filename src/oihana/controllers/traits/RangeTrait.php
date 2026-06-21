<?php

namespace oihana\controllers\traits;

use Exception;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Psr7\Factory\StreamFactory;

use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;

use function oihana\controllers\helpers\applyContentHeaders;
use function oihana\controllers\helpers\parseRangeHeader;
use function oihana\files\assertFile;

/**
 * Serves a file as a PSR-7 response with HTTP byte-range support (seekable downloads,
 * media streaming).
 *
 * - No `Range` header → the full file is streamed (`200`) with `Accept-Ranges: bytes`.
 * - A satisfiable single range → only that slice is returned (`206 Partial Content`)
 *   with a `Content-Range` header.
 * - An unsatisfiable range → `416 Range Not Satisfiable`.
 *
 * Multi-range requests fall back to the full content. The requested slice is buffered
 * (ranges are typically small); the full file is streamed lazily.
 *
 * @package oihana\controllers\traits
 */
trait RangeTrait
{
    use StatusTrait ;

    /**
     * Serves a file honoring the request `Range` header.
     *
     * @param ?Request $request  Optional PSR-7 Request object (read for the `Range` header).
     * @param Response $response The PSR-7 Response object to write into.
     * @param string   $file     Absolute path of the file to serve.
     * @param array    $options  Optional header switches (see {@see oihana\controllers\enums\FileResponseOption}),
     *                           applied to the full-content (`200`) response.
     *
     * @return Response A `200`, `206` or `416` response, or a `500` failure response on error.
     *
     * @example
     * ```php
     * class VideoController extends Controller
     * {
     *     use RangeTrait ;
     *
     *     public function stream( Request $request , Response $response ) : Response
     *     {
     *         // GET with "Range: bytes=0-1023" -> 206 Partial Content (first 1 KiB)
     *         // GET without a Range header     -> 200 with Accept-Ranges: bytes (full file)
     *         return $this->rangeFileResponse( $request , $response , '/var/media/clip.mp4' ) ;
     *     }
     * }
     * ```
     */
    public function rangeFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
    {
        try
        {
            assertFile( $file ) ; // throws FileException (extends Exception) on a missing/unreadable file

            $size     = filesize( $file ) ;
            $response = $response->withHeader( HttpHeader::ACCEPT_RANGES , 'bytes' ) ;

            $range = $request !== null ? parseRangeHeader( $request->getHeaderLine( HttpHeader::RANGE ) , $size ) : null ;

            if ( $range === false )
            {
                return $response
                    ->withHeader( HttpHeader::CONTENT_RANGE , 'bytes */' . $size )
                    ->withStatus( HttpStatusCode::RANGE_NOT_SATISFIABLE ) ;
            }

            if ( $range === null )
            {
                // full content, streamed (lazy read at emit time)
                $response = applyContentHeaders( $response , $file , null , $options , defaultOn: true ) ;
                return $response->withBody( new StreamFactory()->createStreamFromFile( $file ) ) ;
            }

            [ $start , $end ] = $range ;
            $length = $end - $start + 1 ;

            $response = $response
                ->withHeader( HttpHeader::CONTENT_TYPE   , mime_content_type( $file ) )
                ->withHeader( HttpHeader::CONTENT_LENGTH , (string) $length )
                ->withHeader( HttpHeader::CONTENT_RANGE  , sprintf( 'bytes %d-%d/%d' , $start , $end , $size ) )
                ->withStatus( HttpStatusCode::PARTIAL_CONTENT ) ;

            $response->getBody()->write( file_get_contents( $file , false , null , $start , $length ) ) ;

            return $response ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 , $e->getMessage() ) ;
        }
    }
}
