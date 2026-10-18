<?php

namespace oihana\controllers\traits;

use Random\RandomException;
use Throwable;

use oihana\controllers\enums\UploadOption;
use oihana\files\enums\FileMimeType;
use oihana\files\exceptions\FileException;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

use function oihana\core\encoding\randomHex;
use function oihana\core\strings\slugify;
use function oihana\files\getMimeType;
use function oihana\files\normalizeMimeType;
use function oihana\files\validateMimeType;

/**
 * Provides helpers to receive PSR-7 file uploads, validate them and store them on disk.
 *
 * Both helpers delegate the per-file work to the shared {@see self::storeUploadedFile()} method
 * and throw a {@see FileException} on any failure, leaving the response shaping to the
 * controller.
 *
 * A file is checked **before** it takes its place : it is first moved under a provisional name
 * (`.upload-<random>.part`, hidden, with no executable extension), its type is read from its
 * content, and only then is it renamed. A refused file never carries its final name.
 *
 * The stored file is named in one of three ways :
 *
 * - **by default**, a random name and the extension of the type read from the content
 *   (`9f2c4e1a7b3d5f60a8c2e4b6d8f0a1c3.jpg`) — never the name the client chose : a `photo.php`
 *   holding a real image would otherwise be stored, and served, as `photo.php` ;
 * - with {@see UploadOption::CLIENT_FILENAME}, the client name cleaned by `slugify()`, with the
 *   extension of the detected type (`Façade Nord (2).JPG` → `facade-nord-2.jpg`) ; a name already
 *   taken is numbered (`facade-nord-2-2.jpg`) ;
 * - with {@see UploadOption::FILENAME}, the given name, kept or refused but never altered : an
 *   extension that does not match the detected type, or a leading dot, is refused.
 *
 * The extension of a type comes from {@see UploadOption::EXTENSIONS} first, then from
 * `FileMimeType::getExtension()` ; a type without one (an unrecognized content) gives a name
 * without extension. The final name is reserved in one step before the file takes it, so two
 * uploads of the same name at the same time never overwrite each other.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait UploadTrait
{
    /**
     * The highest number tried for a client name already taken, before a random name.
     */
    private const int UPLOAD_MAX_NUMBER = 100 ;

    /**
     * The longest part before the extension kept from a client name.
     */
    private const int UPLOAD_MAX_STEM_LENGTH = 200 ;

    /**
     * The number of random bytes of a random name (twice as many hexadecimal characters).
     */
    private const int UPLOAD_RANDOM_BYTES = 16 ;

    /**
     * The prefix of a provisional file : hidden, never served as code.
     */
    private const string UPLOAD_STAGING_PREFIX = '.upload-' ;

    /**
     * The suffix of a provisional file : no executable extension.
     */
    private const string UPLOAD_STAGING_SUFFIX = '.part' ;

    /**
     * Receives a single uploaded file for the given field, validates it and stores it.
     *
     * @param Request $request The PSR-7 request carrying the uploaded files.
     * @param string $field The uploaded-file field name.
     * @param string $destDir The destination directory.
     * @param array $options Optional switches (see {@see UploadOption}).
     *
     * @return string The absolute path of the stored file.
     *
     * @throws FileException If the field is missing, the upload failed, the size/MIME
     *                       checks fail, the name is refused, or the destination is invalid.
     * @throws RandomException
     * @throws Throwable
     *
     * @example
     * ```php
     * class AvatarController extends Controller
     * {
     *     use UploadTrait ;
     *
     *     public function upload( Request $request , Response $response ) : Response
     *     {
     *         // Stored as <random>.png or <random>.jpg, outside the public web root.
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
     * The {@see UploadOption::FILENAME} option is ignored here : one name cannot be given to
     * several files. Each file gets its own random name, or its own cleaned client name with
     * {@see UploadOption::CLIENT_FILENAME} — numbered when two share it.
     *
     * @param Request $request The PSR-7 request carrying the uploaded files.
     * @param string $field The uploaded-file field name (an array of files).
     * @param string $destDir The destination directory.
     * @param array $options Optional switches (see {@see UploadOption}).
     *
     * @return string[] The absolute paths of the stored files.
     *
     * @throws FileException If the field is missing/not a list, an upload failed, the
     *                       size/MIME checks fail, or the destination is invalid.
     * @throws RandomException
     * @throws Throwable
     */
    public function receiveUploads( Request $request , string $field , string $destDir , array $options = [] ) : array
    {
        $files = $request->getUploadedFiles()[ $field ] ?? null ;

        if( !is_array( $files ) || $files === [] )
        {
            throw new FileException( sprintf( 'No uploaded files found for the field "%s".' , $field ) ) ;
        }

        unset( $options[ UploadOption::FILENAME ] ) ; // one name cannot be given to several files

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
     * Picks the extension of the stored file among the ones of its type : the client's when it is
     * one of them, the first one otherwise, none when the type has none.
     *
     * @param array<int, string> $extensions      The extensions of the detected type (with their dot).
     * @param string             $clientExtension The extension of the client name (with its dot, lower-case), or `''`.
     *
     * @return string The extension, with its dot, or `''`.
     */
    private function chooseUploadExtension( array $extensions , string $clientExtension ) : string
    {
        if( $clientExtension !== '' && in_array( $clientExtension , $extensions , true ) )
        {
            return $clientExtension ;
        }

        return $extensions[ 0 ] ?? '' ;
    }

    /**
     * Returns the extension of a file name, with its dot and lower-case, or `''`.
     *
     * @param string $name The file name.
     *
     * @return string
     */
    private function fileNameExtension( string $name ) : string
    {
        $extension = pathinfo( $name , PATHINFO_EXTENSION ) ;

        return $extension === '' ? '' : '.' . strtolower( $extension ) ;
    }

    /**
     * Returns the extensions of a MIME type : the controller's table first, then the catalogue.
     *
     * @param string|null           $mimeType   The detected type, or `null` when it could not be read.
     * @param array<string, string> $extensions The controller's table, keys normalized.
     *
     * @return array<int, string> The extensions, with their dot ; empty when none is known.
     */
    private function mimeTypeExtensions( ?string $mimeType , array $extensions ) : array
    {
        if( $mimeType === null )
        {
            return [] ;
        }

        $reference = normalizeMimeType( $mimeType ) ;

        if( isset( $extensions[ $reference ] ) )
        {
            return [ $extensions[ $reference ] ] ;
        }

        $known = FileMimeType::getExtension( $reference ) ;

        return match( true )
        {
            is_string( $known ) => [ $known ] ,
            is_array( $known )  => array_values( $known ) ,
            default             => [] ,
        } ;
    }

    /**
     * Reads and checks the {@see UploadOption::EXTENSIONS} table.
     *
     * @param array $options The options.
     *
     * @return array<string, string> The table, keyed by normalized MIME type.
     *
     * @throws FileException When an extension is not a dot followed by letters and digits.
     */
    private function readUploadExtensions( array $options ) : array
    {
        $table = $options[ UploadOption::EXTENSIONS ] ?? [] ;

        if( !is_array( $table ) )
        {
            throw new FileException( 'The extensions option must be an array of MIME type => extension.' ) ;
        }

        $extensions = [] ;

        foreach( $table as $mimeType => $extension )
        {
            if( !is_string( $extension ) || preg_match( '/^\.[A-Za-z0-9]+$/' , $extension ) !== 1 )
            {
                throw new FileException( sprintf( 'The extension of "%s" must be a dot followed by letters and digits.' , $mimeType ) ) ;
            }

            $extensions[ normalizeMimeType( (string) $mimeType ) ] = strtolower( $extension ) ;
        }

        return $extensions ;
    }

    /**
     * Reserves a file name in one step : creates the empty file, failing when it already exists.
     *
     * @param string $path The path to reserve.
     *
     * @return bool True when this call created it.
     */
    private function reserveUploadPath( string $path ) : bool
    {
        $handle = @fopen( $path , 'x' ) ;

        if( $handle === false )
        {
            return false ;
        }

        fclose( $handle ) ;

        return true ;
    }

    /**
     * Chooses and reserves the name of a file stored under its client name.
     *
     * The part before the extension is cleaned by `slugify()` and cut to
     * {@see self::UPLOAD_MAX_STEM_LENGTH} characters ; nothing left gives a random name. A name
     * already taken is numbered (`-2`, `-3`… up to {@see self::UPLOAD_MAX_NUMBER}), then a random
     * name is used ; with `$overwrite`, the name is taken as it is.
     *
     * @param string $directory The destination directory, with its trailing slash.
     * @param string $clientName The client name, without directories.
     * @param array<int, string> $typeExtensions The extensions of the detected type.
     * @param string $clientExtension The extension of the client name, or `''`.
     * @param bool $overwrite Whether an existing file may be replaced.
     * @param string|null $reserved Set to the reserved path, if any.
     *
     * @return string The path the file will take.
     *
     * @throws FileException  When no name can be reserved.
     * @throws RandomException
     */
    private function storeUnderClientName
    (
        string  $directory ,
        string  $clientName ,
        array   $typeExtensions ,
        string  $clientExtension ,
        bool    $overwrite ,
        ?string &$reserved
    )
    : string
    {
        $extension = $this->chooseUploadExtension( $typeExtensions , $clientExtension ) ;
        $stem      = trim( substr( slugify( pathinfo( $clientName , PATHINFO_FILENAME ) ) , 0 , self::UPLOAD_MAX_STEM_LENGTH ) , '-' ) ;

        if( $stem === '' )
        {
            return $this->storeUnderRandomName( $directory , $extension , $reserved ) ;
        }

        if( $overwrite )
        {
            $target = $directory . $stem . $extension ;

            if( $this->reserveUploadPath( $target ) )
            {
                $reserved = $target ;
            }

            return $target ;
        }

        for( $number = 1 ; $number <= self::UPLOAD_MAX_NUMBER ; $number++ )
        {
            $target = $directory . $stem . ( $number === 1 ? '' : '-' . $number ) . $extension ;

            if( $this->reserveUploadPath( $target ) )
            {
                $reserved = $target ;
                return $target ;
            }
        }

        return $this->storeUnderRandomName( $directory , $extension , $reserved ) ;
    }

    /**
     * Chooses and reserves a random name.
     *
     * @param string $directory The destination directory, with its trailing slash.
     * @param string $extension The extension, with its dot, or `''`.
     * @param string|null $reserved Set to the reserved path.
     *
     * @return string The path the file will take.
     *
     * @throws FileException   When no random name can be reserved — the directory refuses to be written.
     * @throws RandomException
     */
    private function storeUnderRandomName( string $directory , string $extension , ?string &$reserved ) : string
    {
        $target = $directory . randomHex( self::UPLOAD_RANDOM_BYTES ) . $extension ;

        if( !$this->reserveUploadPath( $target ) )
        {
            throw new FileException( sprintf( 'The uploaded file could not be stored in "%s".' , $directory ) ) ;
        }

        $reserved = $target ;

        return $target ;
    }

    /**
     * Validates a single uploaded file and moves it into the destination directory.
     *
     * The file is moved under a provisional name, its type is read from its content and checked,
     * its final name is chosen and reserved, and only then is the provisional file renamed. Any
     * failure removes the provisional file and the reservation.
     *
     * @param UploadedFileInterface $uploaded The uploaded file.
     * @param string $destDir The destination directory.
     * @param array $options Optional switches (see {@see UploadOption}).
     *
     * @return string The absolute path of the stored file.
     *
     * @throws FileException On any validation, name or destination error.
     * @throws RandomException
     * @throws Throwable
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

        $filename = $options[ UploadOption::FILENAME ] ?? null ;
        if( $filename !== null )
        {
            $filename = basename( (string) $filename ) ;

            if( $filename === '' )
            {
                throw new FileException( 'The uploaded file has no valid name.' ) ;
            }

            if( str_starts_with( $filename , '.' ) )
            {
                throw new FileException( sprintf( 'The file name "%s" may not start with a dot.' , $filename ) ) ;
            }
        }

        $extensions = $this->readUploadExtensions( $options ) ;

        if( !is_dir( $destDir ) )
        {
            throw new FileException( sprintf( 'The destination directory "%s" does not exist.' , $destDir ) ) ;
        }

        $directory = rtrim( $destDir , '/' ) . '/' ;
        $staging   = $directory . self::UPLOAD_STAGING_PREFIX . randomHex( self::UPLOAD_RANDOM_BYTES ) . self::UPLOAD_STAGING_SUFFIX ;
        $overwrite = ( $options[ UploadOption::OVERWRITE ] ?? false ) === true ;
        $reserved  = null ;

        $uploaded->moveTo( $staging ) ;

        try
        {
            $allowed = $options[ UploadOption::ALLOWED_MIME_TYPES ] ?? null ;
            if( is_array( $allowed ) && $allowed !== [] )
            {
                validateMimeType( $staging , $allowed ) ;
            }

            $typeExtensions  = $this->mimeTypeExtensions( getMimeType( $staging ) , $extensions ) ;
            $clientName      = basename( (string) $uploaded->getClientFilename() ) ;
            $clientExtension = $this->fileNameExtension( $clientName ) ;

            if( $filename !== null )
            {
                // A name the controller chose is kept or refused, never altered.

                $extension = $this->fileNameExtension( $filename ) ;

                if( $extension !== '' && $typeExtensions !== [] && !in_array( $extension , $typeExtensions , true ) )
                {
                    throw new FileException( sprintf
                    (
                        'The file name "%s" does not match the type of its content (expected %s).' ,
                        $filename ,
                        implode( ', ' , $typeExtensions )
                    ) ) ;
                }

                $target = $directory . $filename ;

                if( $this->reserveUploadPath( $target ) )
                {
                    $reserved = $target ;
                }
                elseif( !$overwrite )
                {
                    throw new FileException( sprintf( 'The target file "%s" already exists.' , $target ) ) ;
                }
            }
            elseif( ( $options[ UploadOption::CLIENT_FILENAME ] ?? false ) === true )
            {
                $target = $this->storeUnderClientName( $directory , $clientName , $typeExtensions , $clientExtension , $overwrite , $reserved ) ;
            }
            else
            {
                $target = $this->storeUnderRandomName( $directory , $this->chooseUploadExtension( $typeExtensions , $clientExtension ) , $reserved ) ;
            }

            if( !@rename( $staging , $target ) )
            {
                throw new FileException( sprintf( 'The uploaded file could not be stored as "%s".' , $target ) ) ;
            }

            return $target ;
        }
        catch( Throwable $exception )
        {
            @unlink( $staging ) ;

            if( $reserved !== null )
            {
                @unlink( $reserved ) ; // the empty reservation, never a file stored before
            }

            throw $exception ;
        }
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
