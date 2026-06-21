<?php

namespace oihana\controllers\traits;

use oihana\controllers\enums\UploadOption;
use oihana\files\exceptions\FileException;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

use function oihana\files\validateMimeType;

/**
 * Provides helpers to receive PSR-7 file uploads, validate them and store them on disk.
 *
 * Both helpers delegate the per-file work (error/size/MIME checks, name sanitization,
 * move) to the shared {@see self::storeUploadedFile()} method and throw a
 * {@see FileException} on any failure, leaving the response shaping to the controller.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait UploadTrait
{
    /**
     * Receives a single uploaded file for the given field, validates it and stores it.
     *
     * @param Request $request The PSR-7 request carrying the uploaded files.
     * @param string  $field   The uploaded-file field name.
     * @param string  $destDir The destination directory.
     * @param array   $options Optional switches (see {@see UploadOption}).
     *
     * @return string The absolute path of the stored file.
     *
     * @throws FileException If the field is missing, the upload failed, the size/MIME
     *                       checks fail, or the destination is invalid.
     *
     * @example
     * ```php
     * class AvatarController extends Controller
     * {
     *     use UploadTrait ;
     *
     *     public function upload( Request $request , Response $response ) : Response
     *     {
     *         $path = $this->receiveUpload( $request , 'avatar' , '/var/uploads' , [
     *             UploadOption::MAX_SIZE           => 2_000_000 ,
     *             UploadOption::ALLOWED_MIME_TYPES => [ 'image/png' , 'image/jpeg' ] ,
     *         ] ) ;
     *
     *         return $this->success( $request , $response , [ 'path' => $path ] ) ;
     *     }
     * }
     * ```
     */
    public function receiveUpload( Request $request , string $field , string $destDir , array $options = [] ) : string
    {
        $uploaded = $request->getUploadedFiles()[ $field ] ?? null ;

        if( !$uploaded instanceof UploadedFileInterface )
        {
            throw new FileException( sprintf( 'No uploaded file found for the field "%s".' , $field ) ) ;
        }

        return $this->storeUploadedFile( $uploaded , $destDir , $options ) ;
    }

    /**
     * Receives multiple uploaded files for the given field, validates them and stores them.
     *
     * The {@see UploadOption::FILENAME} option is ignored here: each file keeps its own
     * sanitized client name to avoid collisions.
     *
     * @param Request $request The PSR-7 request carrying the uploaded files.
     * @param string  $field   The uploaded-file field name (an array of files).
     * @param string  $destDir The destination directory.
     * @param array   $options Optional switches (see {@see UploadOption}).
     *
     * @return string[] The absolute paths of the stored files.
     *
     * @throws FileException If the field is missing/not a list, an upload failed, the
     *                       size/MIME checks fail, or the destination is invalid.
     */
    public function receiveUploads( Request $request , string $field , string $destDir , array $options = [] ) : array
    {
        $files = $request->getUploadedFiles()[ $field ] ?? null ;

        if( !is_array( $files ) || $files === [] )
        {
            throw new FileException( sprintf( 'No uploaded files found for the field "%s".' , $field ) ) ;
        }

        unset( $options[ UploadOption::FILENAME ] ) ; // each file keeps its own sanitized client name

        $paths = [] ;

        foreach( $files as $uploaded )
        {
            if( !$uploaded instanceof UploadedFileInterface )
            {
                throw new FileException( sprintf( 'The field "%s" contains a non-file entry.' , $field ) ) ;
            }
            $paths[] = $this->storeUploadedFile( $uploaded , $destDir , $options ) ;
        }

        return $paths ;
    }

    /**
     * Validates a single uploaded file and moves it into the destination directory.
     *
     * @param UploadedFileInterface $uploaded The uploaded file.
     * @param string                $destDir  The destination directory.
     * @param array                 $options  Optional switches (see {@see UploadOption}).
     *
     * @return string The absolute path of the stored file.
     *
     * @throws FileException On any validation or destination error.
     */
    private function storeUploadedFile( UploadedFileInterface $uploaded , string $destDir , array $options = [] ) : string
    {
        $error = $uploaded->getError() ;
        if( $error !== UPLOAD_ERR_OK )
        {
            throw new FileException( $this->uploadErrorMessage( $error ) ) ;
        }

        $maxSize = $options[ UploadOption::MAX_SIZE ] ?? null ;
        $size    = $uploaded->getSize() ;
        if( is_int( $maxSize ) && is_int( $size ) && $size > $maxSize )
        {
            throw new FileException( sprintf( 'The uploaded file exceeds the maximum size of %d bytes (got %d).' , $maxSize , $size ) ) ;
        }

        $filename = basename( (string) ( $options[ UploadOption::FILENAME ] ?? $uploaded->getClientFilename() ) ) ;
        if( $filename === '' )
        {
            throw new FileException( 'The uploaded file has no valid name.' ) ;
        }

        if( !is_dir( $destDir ) )
        {
            throw new FileException( sprintf( 'The destination directory "%s" does not exist.' , $destDir ) ) ;
        }

        $target = rtrim( $destDir , '/' ) . '/' . $filename ;

        $overwrite = $options[ UploadOption::OVERWRITE ] ?? false ;
        if( !$overwrite && file_exists( $target ) )
        {
            throw new FileException( sprintf( 'The target file "%s" already exists.' , $target ) ) ;
        }

        $uploaded->moveTo( $target ) ;

        $allowed = $options[ UploadOption::ALLOWED_MIME_TYPES ] ?? null ;
        if( is_array( $allowed ) && $allowed !== [] )
        {
            try
            {
                validateMimeType( $target , $allowed ) ;
            }
            catch( FileException $exception )
            {
                @unlink( $target ) ; // reject: drop the stored file before propagating
                throw $exception ;
            }
        }

        return $target ;
    }

    /**
     * Maps a PHP `UPLOAD_ERR_*` code to a human-readable message.
     *
     * @param int $error One of the `UPLOAD_ERR_*` constants.
     *
     * @return string The associated message.
     */
    private function uploadErrorMessage( int $error ) : string
    {
        return match( $error )
        {
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.' ,
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.' ,
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.' ,
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.' ,
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder for the upload.' ,
            UPLOAD_ERR_CANT_WRITE => 'Failed to write the uploaded file to disk.' ,
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.' ,
            default               => sprintf( 'The file upload failed (error code %d).' , $error ) ,
        } ;
    }
}
