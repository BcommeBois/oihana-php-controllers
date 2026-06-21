<?php

namespace oihana\controllers\traits;

use Exception;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\FileResponseOption;
use oihana\enums\http\CacheControlDirective;
use oihana\enums\http\HttpHeader;
use oihana\files\enums\CompressionType;
use oihana\files\enums\FileMimeType;
use oihana\files\exceptions\DirectoryException;
use oihana\files\exceptions\FileException;

use function oihana\controllers\helpers\applyContentHeaders;
use function oihana\files\archive\tar\tar;
use function oihana\files\archive\tar\untar;
use function oihana\files\archive\zip\unzip;
use function oihana\files\archive\zip\zip;

/**
 * Provides helpers to bundle files into an archive (tar/zip) and stream it as a
 * PSR-7 download response, and to extract incoming archives back to disk.
 *
 * The download helpers build the archive (delegating creation to the `oihana\files\archive`
 * tar/zip helpers) then delegate header emission, streaming and temporary-file cleanup to the
 * shared {@see self::archiveDownload()} method. The extraction helpers delegate to the same
 * package, which guards against path traversal (Zip Slip) and decompression bombs.
 *
 * @package oihana\controllers\traits
 */
trait ArchiveTrait
{
    use StatusTrait ;

    /**
     * Extracts a tar archive into a destination directory.
     *
     * Thin wrapper around `oihana\files\archive\tar\untar()`, which already guards against
     * path traversal and decompression bombs (see {@see TarOption}).
     *
     * @param string $archive Path of the tar archive to extract.
     * @param string $destDir Directory where the archive is extracted.
     * @param array  $options Optional flags, keyed by {@see TarOption}.
     *
     * @return true|array `true` on success, or the list of entries when `dryRun` is enabled.
     *
     * @throws FileException      If the archive is invalid or inaccessible.
     * @throws DirectoryException If the destination cannot be created or written.
     */
    public function extractTar( string $archive , string $destDir , array $options = [] ) : true|array
    {
        return untar( $archive , $destDir , $options ) ;
    }

    /**
     * Extracts a ZIP archive into a destination directory.
     *
     * Thin wrapper around `oihana\files\archive\zip\unzip()`, which guards against path
     * traversal (Zip Slip) and decompression bombs, and supports a dry run and overwrite
     * control (see {@see ZipOption}).
     *
     * @param string $archive Path of the ZIP archive to extract.
     * @param string $destDir Directory where the archive is extracted.
     * @param array  $options Optional flags, keyed by {@see ZipOption} (`dryRun`, `overwrite`,
     *                        `maxEntries`, `maxSize`, `keepPermissions`).
     *
     * @return true|array `true` on success, or the list of entries when `dryRun` is enabled.
     *
     * @throws FileException      If the archive is invalid/inaccessible, an entry escapes the
     *                            destination, a bomb guard trips, or a target exists without `overwrite`.
     * @throws DirectoryException If the destination directory cannot be created.
     */
    public function extractZip( string $archive , string $destDir , array $options = [] ) : true|array
    {
        return unzip( $archive , $destDir , $options ) ;
    }

    /**
     * Bundles files and/or directories into a tar archive (optionally compressed) and
     * streams it as a download response.
     *
     * Delegates the archive creation to `oihana\files\archive\tar\tar()`. Only `GZIP`,
     * `BZIP2` and `NONE` compressions are supported; any other value raises an
     * `UnsupportedCompressionException`, reported as a `500` response.
     *
     * @param ?Request     $request     Optional PSR-7 Request object (used to build the failure response).
     * @param Response     $response    The PSR-7 Response object to write the archive into.
     * @param string|array $paths       File and/or directory path(s) to add to the archive.
     * @param string       $archive     Absolute path of the tar archive to create.
     * @param ?string      $compression One of {@see CompressionType}: `GZIP` (default), `BZIP2` or `NONE`.
     * @param array        $options     Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the archive, or a `500` failure response on error.
     */
    public function tarResponse
    (
        ?Request     $request ,
        Response     $response ,
        string|array $paths ,
        string       $archive ,
        ?string      $compression = CompressionType::GZIP ,
        array        $options = []
    )
    : Response
    {
        try
        {
            $produced = tar( $paths , $archive , $compression ) ;

            $contentType = match( $compression )
            {
                CompressionType::BZIP2 => FileMimeType::TAR_BZ2 ,  // application/x-bzip2
                CompressionType::NONE  => FileMimeType::TAR[ 1 ] , // application/x-tar
                default                => FileMimeType::TAR_GZ ,   // application/gzip (GZIP)
            } ;

            return $this->archiveDownload( $response , $produced , $contentType , $options ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 , $e->getMessage() ) ;
        }
    }

    /**
     * Bundles a set of files into a ZIP archive and streams it as a download response.
     *
     * Delegates the archive creation to `oihana\files\archive\zip\zip()`, with `$path` used as
     * the preserved root so each entry keeps its name relative to `$path` (i.e. `$path . $name`).
     * If the archive cannot be created, a `500` response is returned.
     *
     * @param ?Request $request  Optional PSR-7 Request object (used to build the failure response).
     * @param Response $response The PSR-7 Response object to write the archive into.
     * @param array    $files    List of file names (relative to `$path`) to add to the archive.
     * @param string   $archive  Absolute path of the ZIP archive to create.
     * @param string   $path     Base directory prepended to each entry in `$files` (preserved root).
     * @param array    $options  Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the archive, or a `500` failure response on error.
     */
    public function zipResponse
    (
        ?Request $request ,
        Response $response ,
        array    $files ,
        string   $archive ,
        string   $path ,
        array    $options = []
    )
    : Response
    {
        try
        {
            $produced = zip( array_map( fn( string $name ) => $path . $name , $files ) , $archive , CompressionType::ZIP , $path ) ;

            return $this->archiveDownload( $response , $produced , FileMimeType::ZIP , $options ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 , $e->getMessage() ) ;
        }
    }

    /**
     * Emits the download headers for a produced archive, streams its content into the
     * response body, then removes the temporary archive file.
     *
     * @param Response $response    The PSR-7 Response object to write the archive into.
     * @param string   $produced    Absolute path of the produced archive file.
     * @param string   $contentType The `Content-Type` to advertise when enabled.
     * @param array    $options     Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the archive body.
     */
    private function archiveDownload( Response $response , string $produced , string $contentType , array $options = [] ) : Response
    {
        $response = applyContentHeaders( $response , $produced , $contentType , $options )
                    ->withHeader( HttpHeader::PRAGMA  , CacheControlDirective::NO_CACHE )
                    ->withHeader( HttpHeader::EXPIRES , '0' ) ;

        $response->getBody()->write( file_get_contents( $produced ) ) ;

        @unlink( $produced ) ; // the archive content is already in the response body; drop the temp file

        return $response ;
    }
}
