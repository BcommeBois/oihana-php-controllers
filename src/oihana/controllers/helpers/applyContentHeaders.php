<?php

namespace oihana\controllers\helpers ;

use Psr\Http\Message\ResponseInterface as Response;

use oihana\controllers\enums\FileResponseOption;
use oihana\enums\http\HttpHeader;

/**
 * Applies the download content headers (`Content-Type`, `Content-Length`,
 * `Content-Disposition`) to a PSR-7 response, toggled by {@see FileResponseOption}.
 *
 * Shared by the file/archive/image/encryption response helpers so the header logic
 * lives in one place.
 *
 * @param Response $response    The PSR-7 response to decorate.
 * @param string   $file        Path of the file whose size/MIME type back the headers (must exist).
 * @param ?string  $contentType The `Content-Type` to advertise; when null, `mime_content_type($file)` is used.
 * @param array    $options     Header switches keyed by {@see FileResponseOption}:
 *                              - `useContentType`        (bool)   emit `Content-Type`.
 *                              - `useContentLength`      (bool)   emit `Content-Length` (file size).
 *                              - `useContentDisposition` (bool)   emit `Content-Disposition`.
 *                              - `contentDisposition`    (string) value to use (defaults to `attachment; filename=<basename>`).
 * @param bool     $defaultOn   Default state of the three switches when absent from `$options`.
 *
 * @return Response The response with the requested headers applied.
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function applyContentHeaders
(
    Response $response ,
    string   $file ,
    ?string  $contentType = null ,
    array    $options     = [] ,
    bool     $defaultOn   = true
)
: Response
{
    $contentDisposition    = $options[ FileResponseOption::CONTENT_DISPOSITION     ] ?? 'attachment; filename=' . basename( $file ) ;
    $useContentType        = $options[ FileResponseOption::USE_CONTENT_TYPE        ] ?? $defaultOn ;
    $useContentLength      = $options[ FileResponseOption::USE_CONTENT_LENGTH      ] ?? $defaultOn ;
    $useContentDisposition = $options[ FileResponseOption::USE_CONTENT_DISPOSITION ] ?? $defaultOn ;

    if( $useContentType )
    {
        $response = $response->withHeader( HttpHeader::CONTENT_TYPE , $contentType ?? mime_content_type( $file ) ) ;
    }

    if( $useContentLength )
    {
        $response = $response->withHeader( HttpHeader::CONTENT_LENGTH , (string) filesize( $file ) ) ;
    }

    if( $useContentDisposition )
    {
        $response = $response->withHeader( HttpHeader::CONTENT_DISPOSITION , $contentDisposition ) ;
    }

    return $response ;
}
