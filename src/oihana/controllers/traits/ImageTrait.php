<?php

namespace oihana\controllers\traits;

use Exception;
use Imagick;
use ImagickException;
use ImagickPixel;

use oihana\controllers\enums\FileResponseOption;
use oihana\controllers\enums\ImagickResponseOption;
use oihana\controllers\enums\ResizeOption;
use oihana\enums\http\HttpHeader;

use oihana\core\maths\Dimension;

use function oihana\core\maths\aspectFit;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Psr7\Factory\StreamFactory;

use function oihana\files\assertFile;

/**
 * Provides image helpers built on top of Imagick: dimension inspection, on-the-fly
 * resizing and shadow effects, and PSR-7 responses that stream an image file or an
 * in-memory Imagick blob.
 *
 * Transform and header behavior is driven by the `$options` arrays, keyed by the
 * {@see ResizeOption}, {@see ImagickResponseOption} and {@see FileResponseOption} enums.
 * The images root path can be centralized through {@see self::initializeImagePath()}.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ImageTrait
{
    use StatusTrait ;

    /**
     * The default Imagick compression of the output image.
     */
    public const int DEFAULT_COMPRESSION = Imagick::COMPRESSION_JPEG ;

    /**
     * The default output image format.
     */
    public const string DEFAULT_FORMAT = 'jpg' ;

    /**
     * The default maximum height used by {@see self::resize()}.
     */
    public const int DEFAULT_MAX_HEIGHT = 1200 ;

    /**
     * The default maximum width used by {@see self::resize()}.
     */
    public const int DEFAULT_MAX_WIDTH = 1920 ;

    /**
     * The default Imagick compression quality (0-100).
     */
    public const int DEFAULT_QUALITY = 70 ;

    /**
     * The key used to initialize the images root path from an array.
     */
    public const string IMAGE_PATH = 'imagePath' ;

    /**
     * The root path where the images are stored on the server.
     *
     * @var string
     */
    public string $imagePath = '' ;

    /**
     * Returns the dimensions (width and height) of an image.
     *
     * @param Imagick|string $image The Imagick instance or the path of the image file.
     *
     * @return ?array An associative array with `width` and `height` keys.
     *
     * @throws ImagickException If the image cannot be read or decoded by Imagick.
     */
    public function getImageDimensions( Imagick|string $image ) :?array
    {
        if( is_string( $image ) )
        {
            $image = new Imagick( $image ) ;
        }
        return [ ResizeOption::WIDTH => $image->getImageWidth() , ResizeOption::HEIGHT => $image->getImageHeight() ] ;
    }

    /**
     * Returns the height of an image.
     *
     * @param Imagick|string $image The Imagick instance or the path of the image file.
     *
     * @return int The image height in pixels.
     *
     * @throws ImagickException If the image cannot be read or decoded by Imagick.
     */
    public function getImageHeight( Imagick|string $image ) :int
    {
        if( is_string( $image ) )
        {
            $image = new Imagick( $image ) ;
        }
        return $image->getImageHeight() ;
    }

    /**
     * Returns the root path where the images are stored on the server.
     *
     * @return string The images root path.
     */
    public function getImagePath() :string
    {
        return $this->imagePath ;
    }

    /**
     * Returns the width of an image.
     *
     * @param Imagick|string $image The Imagick instance or the path of the image file.
     *
     * @return int The image width in pixels.
     *
     * @throws ImagickException If the image cannot be read or decoded by Imagick.
     */
    public function getImageWidth( Imagick|string $image ) :int
    {
        if( is_string( $image ) )
        {
            $image = new Imagick( $image ) ;
        }
        return $image->getImageWidth() ;
    }

    /**
     * Streams an image file as a PSR-7 response.
     *
     * The file is validated with {@see assertFile()} first; a missing or unreadable
     * file is reported as a `500` response (see the `catch` below). Optional content
     * headers are toggled via `$options`, keyed by {@see FileResponseOption}.
     *
     * @param ?Request $request  Optional PSR-7 Request object (used to build the failure response).
     * @param Response $response The PSR-7 Response object to write the image into.
     * @param string   $file     Absolute path of the image file to send.
     * @param array    $options  Optional header switches (see {@see FileResponseOption}).
     *
     * @return Response The response carrying the image body, or a `500` failure response on error.
     */
    public function imageResponse
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

            $contentDisposition    = $options[ FileResponseOption::CONTENT_DISPOSITION     ] ?? null ;
            $format                = $options[ FileResponseOption::FORMAT                  ] ?? self::DEFAULT_FORMAT ;
            $useContentDisposition = $options[ FileResponseOption::USE_CONTENT_DISPOSITION ] ?? false ;
            $useContentLength      = $options[ FileResponseOption::USE_CONTENT_LENGTH      ] ?? true ;
            $useContentType        = $options[ FileResponseOption::USE_CONTENT_TYPE        ] ?? true ;

            if( $useContentType )
            {
                $format = !empty( $format ) ? 'image/' . $format : mime_content_type( $file ) ;
                $response = $response->withHeader( HttpHeader::CONTENT_TYPE  , $format ) ;
            }

            if( $useContentLength )
            {
                $response = $response->withHeader( HttpHeader::CONTENT_LENGTH , (string) filesize( $file ) ) ;
            }

            if( $useContentDisposition )
            {
                $response = $response->withHeader( HttpHeader::CONTENT_DISPOSITION , $contentDisposition ) ;
            }

            // streamed (lazy read at emit time) so large images are not loaded into memory
            return $response->withBody( new StreamFactory()->createStreamFromFile( $file ) ) ;
        }
        catch( Exception $e )
        {
            return $this->fail( $request , $response , 500 ,  $e->getMessage() ) ;
        }
    }

    /**
     * Returns an image response, applying optional Imagick transforms beforehand.
     *
     * @param Response       $response The PSR-7 Response object to write the image into.
     * @param string|Imagick $image    The Imagick instance or the path of the image file.
     * @param array          $options  Optional transform/header switches (see {@see ImagickResponseOption} and {@see FileResponseOption}).
     *
     * @return Response The response carrying the transformed image, or a `500` failure response on error.
     */
    public function imagickResponse( Response $response , string|Imagick $image , array $options = [] ): Response
    {
        try
        {
            $compression           = $options[ ImagickResponseOption::COMPRESSION          ] ?? self::DEFAULT_COMPRESSION ;
            $contentDisposition    = $options[ FileResponseOption::CONTENT_DISPOSITION      ] ?? null ;
            $quality               = $options[ ImagickResponseOption::QUALITY               ] ?? self::DEFAULT_QUALITY ;
            $gray                  = $options[ ImagickResponseOption::GRAY                  ] ?? false ;
            $format                = $options[ FileResponseOption::FORMAT                   ] ?? self::DEFAULT_FORMAT ;
            $strip                 = $options[ ImagickResponseOption::STRIP                 ] ?? false ;
            $useContentDisposition = $options[ FileResponseOption::USE_CONTENT_DISPOSITION  ] ?? false ;
            $useContentLength      = $options[ FileResponseOption::USE_CONTENT_LENGTH       ] ?? true ;
            $useContentType        = $options[ FileResponseOption::USE_CONTENT_TYPE         ] ?? true ;

            if( is_string( $image ) )
            {
                $image = new Imagick( $image ) ;
            }

            if ( $gray )
            {
                $image->modulateImage(100, 0, 100);
            }

            if ( !empty( $format ) )
            {
                $image->setImageFormat( $format );
            }

            $image->setImageCompression( $compression );
            $image->setImageCompressionQuality( $quality );

            if ( $strip )
            {
                $image->stripImage();
            }

            if( $useContentType )
            {
                $response = $response->withHeader( HttpHeader::CONTENT_TYPE , 'image/' . strtolower( $image->getImageFormat() ) ) ;
            }

            if( $useContentLength )
            {
                $response = $response->withHeader( HttpHeader::CONTENT_LENGTH , (string) strlen( $image->__toString() ) ) ;
            }

            if( $useContentDisposition )
            {
                $response = $response->withHeader( HttpHeader::CONTENT_DISPOSITION , $contentDisposition ) ;
            }

            $response->getBody()->write( $image->getImageBlob() );

            $image->clear() ;

            return $response ;
        }
        catch( Exception $e )
        {
            return $this->fail( null , $response , 500 , $e->getMessage() ) ;
        }
    }

    /**
     * Initializes the images root path.
     *
     * @param string|array $init Either the path string directly, or an array
     *                           carrying it under the {@see self::IMAGE_PATH} key.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializeImagePath( string|array $init = [] ) :static
    {
        $this->imagePath = is_string( $init ) ? $init : ( $init[ self::IMAGE_PATH ] ?? '' ) ;
        return $this ;
    }

    /**
     * Resizes an image, clamping it to the configured maximum dimensions and optionally
     * fitting it to an explicit width and/or height.
     *
     * When only one of `$w`/`$h` is given, the other is derived from {@see aspectFit()} so the
     * aspect ratio is preserved. A `null` input is returned untouched.
     *
     * Ex: ../image?resize=true&w=50&h=50
     *
     * @param Imagick|string|null $image   The url of the image file or the Imagick object reference to transform.
     * @param ?int                $w       Optional explicit target width.
     * @param ?int                $h       Optional explicit target height.
     * @param array               $options Optional overrides (see {@see ResizeOption}).
     *
     * @return string|Imagick|null The transformed Imagick instance, or the original input when no transform applies.
     *
     * @throws ImagickException If the image cannot be read, decoded or resized by Imagick.
     */
    public function resize( Imagick|string|null $image , ?int $w = null , ?int $h = null , array $options = [] ):string|Imagick|null
    {
        if( $image )
        {
            if( is_string( $image ) )
            {
                $image = new Imagick( $image ) ;
            }

            $geometry  = $image->getImageGeometry() ;
            $width     = $options[ ResizeOption::WIDTH      ] ?? $geometry[ ResizeOption::WIDTH  ] ;
            $height    = $options[ ResizeOption::HEIGHT     ] ?? $geometry[ ResizeOption::HEIGHT ] ;
            $maxWidth  = $options[ ResizeOption::MAX_WIDTH  ] ?? self::DEFAULT_MAX_WIDTH ;
            $maxHeight = $options[ ResizeOption::MAX_HEIGHT ] ?? self::DEFAULT_MAX_HEIGHT ;

            if( ( $width > $maxWidth ) || ( $height > $maxHeight ) )
            {
                $image->resizeImage
                (
                    $maxWidth, $maxHeight, Imagick::FILTER_LANCZOS, 1.1, true
                ) ;
            }

            $hasW = is_int($w) && ( $w > 0 ) ;
            $hasH = is_int($h) && ( $h > 0 ) ;

            if( $hasW && $hasH )
            {
                $image->resizeImage( $w, $h, Imagick::FILTER_LANCZOS, 1.1, true );
            }
            elseif( $hasW || $hasH )
            {
                $fit = aspectFit( $width , $height , $hasW ? $w : null , $hasH ? $h : null ) ;

                $image->resizeImage( $fit[ Dimension::WIDTH ] , $fit[ Dimension::HEIGHT ] , Imagick::FILTER_LANCZOS, 1.1 , true ) ;
            }

        }

        return $image ;
    }

    /**
     * Applies a drop shadow over an image.
     *
     * The shadow is described by a comma-separated `opacity,sigma,x,y` string; any other
     * shape (or a `null` image) leaves the input unchanged.
     *
     * Ex: ../image?shadow=true
     * Ex: ../image?shadow=60,4,10,20
     *
     * @param Imagick|string|null $image The url of the image file or the Imagick object reference to transform.
     * @param ?string             $value The shadow definition: `opacity,sigma,x,y`.
     *
     * @return string|Imagick|null The composited Imagick instance, or the original input when no shadow applies.
     *
     * @throws ImagickException If the image cannot be read, decoded or composited by Imagick.
     */
    public function shadow( Imagick|string|null $image , null|string $value = null  ):string|Imagick|null
    {
        if( !empty( $value ) && $image !== null)
        {
            if( is_string( $image ) )
            {
                $image = new Imagick( $image ) ;
            }

            $shadow = explode( ',' , $value ) ;
            if( is_array( $shadow ) && ( count($shadow) == 4 ) )
            {
                [ $opacity, $sigma, $x, $y ] = $shadow ;
                $shadowImage = clone $image ;
                $shadowImage->setImageBackgroundColor( new ImagickPixel( 'black' ) ) ;
                $shadowImage->shadowImage( $opacity , $sigma , $x , $y ) ;
                $shadowImage->compositeImage( $image, Imagick::COMPOSITE_OVER, 0, 0 );
                return $shadowImage ;
            }
        }

        return $image ;
    }
}
