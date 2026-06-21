<?php

namespace tests\oihana\controllers\traits;

use Imagick;
use ImagickPixel;

use oihana\controllers\enums\FileResponseOption;
use oihana\controllers\enums\ImagickResponseOption;
use oihana\controllers\enums\ResizeOption;
use oihana\controllers\traits\ImageTrait;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ImageTraitTest extends TestCase
{
    private object $mock;

    private string $dir;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use ImageTrait;
        };

        $this->dir = sys_get_temp_dir() . '/image_trait_' . uniqid();
        mkdir( $this->dir );
    }

    protected function tearDown(): void
    {
        foreach ( glob( $this->dir . '/*' ) ?: [] as $f )
        {
            @unlink( $f );
        }
        @rmdir( $this->dir );
    }

    // ----------------------------------------------------------- helpers

    private function image( int $w = 100 , int $h = 80 , string $color = 'red' ): Imagick
    {
        $image = new Imagick();
        $image->newImage( $w , $h , new ImagickPixel( $color ) );
        $image->setImageFormat( 'png' );
        return $image;
    }

    private function imageFile( int $w = 100 , int $h = 80 ): string
    {
        $path  = $this->dir . '/img_' . uniqid() . '.png';
        $image = $this->image( $w , $h );
        $image->writeImage( $path );
        $image->clear();
        return $path;
    }

    private function response(): ResponseInterface
    {
        $stream = $this->createStub( StreamInterface::class );
        $stream->method('write')->willReturnCallback( fn( $data ) => strlen( (string) $data ) );

        $response = $this->createStub( ResponseInterface::class );
        $response->method('getBody')->willReturn( $stream );
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnSelf();
        $response->method('withBody')->willReturnSelf();

        return $response;
    }

    // ----------------------------------------------------------- imagePath

    public function testInitializeImagePathFromString(): void
    {
        $result = $this->mock->initializeImagePath( '/var/images' );

        $this->assertSame( $this->mock , $result );
        $this->assertSame( '/var/images' , $this->mock->imagePath );
        $this->assertSame( '/var/images' , $this->mock->getImagePath() );
    }

    public function testInitializeImagePathFromArray(): void
    {
        $this->mock->initializeImagePath([ $this->mock::IMAGE_PATH => '/srv/pics' ]);
        $this->assertSame( '/srv/pics' , $this->mock->getImagePath() );
    }

    public function testInitializeImagePathDefaultsToEmpty(): void
    {
        $this->mock->initializeImagePath();
        $this->assertSame( '' , $this->mock->getImagePath() );
    }

    // ----------------------------------------------------------- dimensions / width / height

    public function testGetImageDimensionsFromPath(): void
    {
        $dimensions = $this->mock->getImageDimensions( $this->imageFile( 120 , 90 ) );

        $this->assertSame( 120 , $dimensions[ ResizeOption::WIDTH ] );
        $this->assertSame( 90  , $dimensions[ ResizeOption::HEIGHT ] );
    }

    public function testGetImageDimensionsFromImagickInstance(): void
    {
        $dimensions = $this->mock->getImageDimensions( $this->image( 64 , 48 ) );

        $this->assertSame( 64 , $dimensions[ ResizeOption::WIDTH ] );
        $this->assertSame( 48 , $dimensions[ ResizeOption::HEIGHT ] );
    }

    public function testGetImageHeightFromPathAndInstance(): void
    {
        $this->assertSame( 90 , $this->mock->getImageHeight( $this->imageFile( 120 , 90 ) ) );
        $this->assertSame( 48 , $this->mock->getImageHeight( $this->image( 64 , 48 ) ) );
    }

    public function testGetImageWidthFromPathAndInstance(): void
    {
        $this->assertSame( 120 , $this->mock->getImageWidth( $this->imageFile( 120 , 90 ) ) );
        $this->assertSame( 64  , $this->mock->getImageWidth( $this->image( 64 , 48 ) ) );
    }

    // ----------------------------------------------------------- imageResponse

    public function testImageResponseWithAllHeaders(): void
    {
        $response = $this->response();

        $result = $this->mock->imageResponse
        (
            null ,
            $response ,
            $this->imageFile() ,
            [
                FileResponseOption::USE_CONTENT_TYPE        => true ,
                FileResponseOption::USE_CONTENT_LENGTH      => true ,
                FileResponseOption::USE_CONTENT_DISPOSITION => true ,
                FileResponseOption::CONTENT_DISPOSITION     => 'inline; filename=img.png' ,
                FileResponseOption::FORMAT                  => 'png' ,
            ]
        );

        $this->assertSame( $response , $result );
    }

    public function testImageResponseUsesMimeWhenFormatEmpty(): void
    {
        $response = $this->response();

        $result = $this->mock->imageResponse
        (
            null ,
            $response ,
            $this->imageFile() ,
            [ FileResponseOption::FORMAT => '' ] // falls back to mime_content_type()
        );

        $this->assertSame( $response , $result );
    }

    public function testImageResponseMissingFileGoesThroughCatch(): void
    {
        $response = $this->response();

        $result = $this->mock->imageResponse( null , $response , $this->dir . '/nope.png' );

        $this->assertSame( $response , $result );
    }

    // ----------------------------------------------------------- imagickResponse

    public function testImagickResponseFromImagickWithTransforms(): void
    {
        $response = $this->response();

        $result = $this->mock->imagickResponse
        (
            $response ,
            $this->image( 100 , 80 ) ,
            [
                ImagickResponseOption::GRAY                 => true ,
                ImagickResponseOption::STRIP                => true ,
                FileResponseOption::FORMAT                  => 'jpg' ,
                FileResponseOption::USE_CONTENT_TYPE        => true ,
                FileResponseOption::USE_CONTENT_LENGTH      => true ,
                FileResponseOption::USE_CONTENT_DISPOSITION => true ,
                FileResponseOption::CONTENT_DISPOSITION     => 'inline' ,
            ]
        );

        $this->assertSame( $response , $result );
    }

    public function testImagickResponseFromStringPath(): void
    {
        $response = $this->response();

        $result = $this->mock->imagickResponse( $response , $this->imageFile( 50 , 50 ) );

        $this->assertSame( $response , $result );
    }

    /**
     * Non-regression: a failing Imagick load must go through the catch and
     * return a proper 500 response instead of raising a TypeError because
     * fail() was called with the wrong argument slots.
     *
     * @see CHANGELOG "Fixed > Controllers"
     */
    public function testImagickResponseFailureReturnsResponseInsteadOfTypeError(): void
    {
        $response = $this->response();

        $result = $this->mock->imagickResponse( $response , '/this/path/does/not/exist.jpg' );

        $this->assertSame( $response , $result );
    }

    // ----------------------------------------------------------- resize

    public function testResizeReturnsNullWhenImageIsNull(): void
    {
        $this->assertNull( $this->mock->resize( null ) );
    }

    public function testResizeScalesDownWhenLargerThanMax(): void
    {
        $image  = $this->image( 100 , 80 );
        $result = $this->mock->resize( $image , null , null , [ ResizeOption::MAX_WIDTH => 10 , ResizeOption::MAX_HEIGHT => 10 ] );

        $this->assertInstanceOf( Imagick::class , $result );
        $this->assertLessThanOrEqual( 10 , $result->getImageWidth() );
    }

    public function testResizeWithExplicitWidthAndHeight(): void
    {
        $result = $this->mock->resize( $this->image( 100 , 80 ) , 50 , 40 );

        $this->assertSame( 50 , $result->getImageWidth() );
        $this->assertSame( 40 , $result->getImageHeight() );
    }

    public function testResizeWithWidthOnlyKeepsRatio(): void
    {
        $result = $this->mock->resize( $this->image( 100 , 80 ) , 50 , null );

        $this->assertInstanceOf( Imagick::class , $result );
        $this->assertSame( 50 , $result->getImageWidth() );
    }

    public function testResizeWithHeightOnlyKeepsRatio(): void
    {
        $result = $this->mock->resize( $this->image( 100 , 80 ) , null , 40 );

        $this->assertInstanceOf( Imagick::class , $result );
        $this->assertSame( 40 , $result->getImageHeight() );
    }

    public function testResizeFromStringPath(): void
    {
        $result = $this->mock->resize( $this->imageFile( 100 , 80 ) , 30 , 30 );

        $this->assertInstanceOf( Imagick::class , $result );
    }

    // ----------------------------------------------------------- shadow

    public function testShadowAppliesWhenValueHasFourParts(): void
    {
        $result = $this->mock->shadow( $this->image( 100 , 80 ) , '60,4,10,20' );

        $this->assertInstanceOf( Imagick::class , $result );
    }

    public function testShadowFromStringPath(): void
    {
        $result = $this->mock->shadow( $this->imageFile( 60 , 60 ) , '50,3,5,5' );

        $this->assertInstanceOf( Imagick::class , $result );
    }

    public function testShadowReturnsImageUnchangedWhenValueEmpty(): void
    {
        $image = $this->image( 100 , 80 );
        $this->assertSame( $image , $this->mock->shadow( $image , '' ) );
        $this->assertSame( $image , $this->mock->shadow( $image ) );
    }

    public function testShadowReturnsImageUnchangedWhenNotFourParts(): void
    {
        $image = $this->image( 100 , 80 );
        $this->assertSame( $image , $this->mock->shadow( $image , '60,4' ) );
    }
}
