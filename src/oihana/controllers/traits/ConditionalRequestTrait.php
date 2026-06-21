<?php

namespace oihana\controllers\traits;

use Exception;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Psr7\Factory\StreamFactory;

use oihana\controllers\enums\ConditionalRequestOption;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\HttpStatusCode;

use function oihana\controllers\helpers\applyContentHeaders;
use function oihana\controllers\helpers\computeETag;
use function oihana\controllers\helpers\etagMatches;
use function oihana\files\assertFile;

/**
 * Serves a file as a PSR-7 response with HTTP conditional-request support (`ETag` /
 * `Last-Modified` validators and the `304 Not Modified` short-circuit).
 *
 * On every call the response carries an `ETag` and a `Last-Modified` header. The request
 * preconditions are then evaluated, with `If-None-Match` taking precedence over
 * `If-Modified-Since` (RFC 7232):
 *
 * - When the precondition matches, a bodyless `304 Not Modified` is returned (still carrying
 *   the validators).
 * - Otherwise the full file is streamed (`200`), lazily, like {@see FileTrait::fileResponse()}.
 *
 * The validating `ETag` is built by {@see computeETag()} — from the file metadata
 * (`mtime`-`size`) by default, or from its content (`md5_file()`) when the
 * {@see ConditionalRequestOption::HASH_CONTENT} option is set.
 *
 * @package oihana\controllers\traits
 */
trait ConditionalRequestTrait
{
    use StatusTrait ;

    /**
     * Serves a file honoring the request conditional headers (`If-None-Match` / `If-Modified-Since`).
     *
     * @param ?Request $request  Optional PSR-7 Request object (read for the precondition headers).
     * @param Response $response The PSR-7 Response object to write into.
     * @param string   $file     Absolute path of the file to serve.
     * @param array    $options  Optional switches keyed by {@see ConditionalRequestOption}
     *                           (`weak`, `hashContent`) and {@see oihana\controllers\enums\FileResponseOption}
     *                           (content headers, applied to the `200` response).
     *
     * @return Response A `200` (full content), `304 Not Modified`, or `500` failure response.
     *
     * @example
     * ```php
     * class AssetController extends Controller
     * {
     *     use ConditionalRequestTrait ;
     *
     *     public function asset( Request $request , Response $response ) : Response
     *     {
     *         // 1st request          -> 200 with ETag + Last-Modified
     *         // request with a matching If-None-Match -> 304 Not Modified (empty body)
     *         return $this->conditionalFileResponse( $request , $response , '/var/assets/app.css' ) ;
     *     }
     * }
     * ```
     */
    public function conditionalFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
    {
        try
        {
            assertFile( $file ) ; // throws FileException (extends Exception) on a missing/unreadable file

            $weak         = $options[ ConditionalRequestOption::WEAK         ] ?? false ;
            $hashContent  = $options[ ConditionalRequestOption::HASH_CONTENT ] ?? false ;

            $etag         = computeETag( $file , $weak , $hashContent ) ;
            $lastModified = filemtime( $file ) ;

            // The validators are carried by the 304 too (RFC 7232).
            $response = $response
                ->withHeader( HttpHeader::ETAG          , $etag )
                ->withHeader( HttpHeader::LAST_MODIFIED , gmdate( 'D, d M Y H:i:s' , $lastModified ) . ' GMT' ) ;

            // If-None-Match takes precedence over If-Modified-Since.
            $ifNoneMatch = $request?->getHeaderLine( HttpHeader::IF_NONE_MATCH ) ?? '' ;
            if ( $ifNoneMatch !== '' )
            {
                $notModified = etagMatches( $ifNoneMatch , $etag ) ;
            }
            else
            {
                $since       = strtotime( $request?->getHeaderLine( HttpHeader::IF_MODIFIED_SINCE ) ?? '' ) ;
                $notModified = $since !== false && $lastModified <= $since ;
            }

            if ( $notModified )
            {
                return $response->withStatus( HttpStatusCode::NOT_MODIFIED ) ; // empty body
            }

            $response = applyContentHeaders( $response , $file , null , $options , defaultOn: true ) ;
            return $response->withBody( new StreamFactory()->createStreamFromFile( $file ) ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 , $e->getMessage() ) ;
        }
    }
}
