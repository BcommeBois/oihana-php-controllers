<?php

namespace tests\oihana\controllers\traits;

use Imagick;
use ImagickPixel;

use oihana\controllers\enums\UploadOption;
use oihana\controllers\traits\UploadTrait;
use oihana\files\enums\ImageMimeType;
use oihana\files\exceptions\FileException;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

final class UploadTraitTest extends TestCase
{
    private object $mock;

    private string $dir;
    private string $dest;
    private string $pngSource;
    private string $txtSource;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use UploadTrait;
        };

        $this->dir  = sys_get_temp_dir() . '/upload_trait_' . uniqid();
        mkdir( $this->dir );

        $this->dest = $this->dir . '/dest';
        mkdir( $this->dest );

        $this->pngSource = $this->dir . '/source.png';
        $image = new Imagick();
        $image->newImage( 2 , 2 , new ImagickPixel( 'red' ) );
        $image->setImageFormat( 'png' );
        $image->writeImage( $this->pngSource );
        $image->clear();

        $this->txtSource = $this->dir . '/source.txt';
        file_put_contents( $this->txtSource , 'plain text' );
    }

    protected function tearDown(): void
    {
        foreach ( glob( $this->dest . '/*' ) ?: [] as $f ) { @unlink( $f ); }
        @rmdir( $this->dest );
        foreach ( glob( $this->dir . '/*' ) ?: [] as $f ) { @unlink( $f ); }
        @rmdir( $this->dir );
    }

    /**
     * Builds an UploadedFileInterface stub whose moveTo() copies $source to the target.
     */
    private function uploadedFile( string $source , ?string $clientName = null , int $error = UPLOAD_ERR_OK , ?int $size = null ): UploadedFileInterface
    {
        $file = $this->createStub( UploadedFileInterface::class );
        $file->method('getError')->willReturn( $error );
        $file->method('getSize')->willReturn( $size ?? ( is_file( $source ) ? filesize( $source ) : 0 ) );
        $file->method('getClientFilename')->willReturn( $clientName ?? basename( $source ) );
        $file->method('moveTo')->willReturnCallback( fn( $target ) => @copy( $source , $target ) );
        return $file;
    }

    private function request( array $uploadedFiles ): Request
    {
        $request = $this->createStub( Request::class );
        $request->method('getUploadedFiles')->willReturn( $uploadedFiles );
        return $request;
    }

    // ----------------------------------------------------------- receiveUpload

    public function testReceiveUploadStoresFile(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest );

        $this->assertSame( $this->dest . '/photo.png' , $path );
        $this->assertFileExists( $path );
    }

    public function testReceiveUploadWithAllowedMimeType(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG ] ] );

        $this->assertFileExists( $path );
    }

    public function testReceiveUploadRejectsDisallowedMimeAndRemovesFile(): void
    {
        $request = $this->request([ 'doc' => $this->uploadedFile( $this->txtSource , 'note.txt' ) ]);

        try
        {
            $this->mock->receiveUpload( $request , 'doc' , $this->dest , [ UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG ] ] );
            $this->fail( 'Expected a FileException for a disallowed MIME type.' );
        }
        catch ( FileException )
        {
            $this->assertFileDoesNotExist( $this->dest . '/note.txt' , 'the rejected file must be removed' );
        }
    }

    public function testReceiveUploadMissingFieldThrows(): void
    {
        $this->expectException( FileException::class );
        $this->mock->receiveUpload( $this->request([]) , 'avatar' , $this->dest );
    }

    public function testReceiveUploadWithUploadErrorThrows(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' , UPLOAD_ERR_INI_SIZE ) ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUpload( $request , 'avatar' , $this->dest );
    }

    public function testReceiveUploadSizeExceededThrows(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' , size: 10_000 ) ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::MAX_SIZE => 100 ] );
    }

    public function testReceiveUploadEmptyNameThrows(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , '' ) ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUpload( $request , 'avatar' , $this->dest );
    }

    public function testReceiveUploadMissingDestDirThrows(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUpload( $request , 'avatar' , $this->dir . '/missing' );
    }

    public function testReceiveUploadSanitizesClientFilename(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , '../../evil.png' ) ]);

        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest );

        $this->assertSame( $this->dest . '/evil.png' , $path ); // path components stripped
    }

    public function testReceiveUploadCustomFilename(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::FILENAME => 'renamed.png' ] );

        $this->assertSame( $this->dest . '/renamed.png' , $path );
    }

    public function testReceiveUploadOverwriteGuard(): void
    {
        file_put_contents( $this->dest . '/photo.png' , 'existing' );
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        // without overwrite -> rejected
        try
        {
            $this->mock->receiveUpload( $request , 'avatar' , $this->dest );
            $this->fail( 'Expected a FileException when the target exists and overwrite is false.' );
        }
        catch ( FileException ) {}

        // with overwrite -> stored
        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::OVERWRITE => true ] );
        $this->assertFileExists( $path );
    }

    public function testUploadErrorMessagesForEachCode(): void
    {
        $cases =
        [
            UPLOAD_ERR_INI_SIZE   => 'upload_max_filesize' ,
            UPLOAD_ERR_FORM_SIZE  => 'MAX_FILE_SIZE' ,
            UPLOAD_ERR_PARTIAL    => 'partially' ,
            UPLOAD_ERR_NO_FILE    => 'No file' ,
            UPLOAD_ERR_NO_TMP_DIR => 'temporary folder' ,
            UPLOAD_ERR_CANT_WRITE => 'write' ,
            UPLOAD_ERR_EXTENSION  => 'extension' ,
            99                    => 'error code 99' , // default arm
        ];

        foreach ( $cases as $code => $needle )
        {
            $request = $this->request([ 'f' => $this->uploadedFile( '' , 'x' , $code ) ]);
            try
            {
                $this->mock->receiveUpload( $request , 'f' , $this->dest );
                $this->fail( 'Expected a FileException for upload error code ' . $code );
            }
            catch ( FileException $e )
            {
                $this->assertStringContainsString( $needle , $e->getMessage() );
            }
        }
    }

    // ----------------------------------------------------------- receiveUploads

    public function testReceiveUploadsStoresAll(): void
    {
        $request = $this->request
        ([
            'docs' =>
            [
                $this->uploadedFile( $this->pngSource , 'a.png' ) ,
                $this->uploadedFile( $this->pngSource , 'b.png' ) ,
            ]
        ]);

        $paths = $this->mock->receiveUploads( $request , 'docs' , $this->dest );

        $this->assertCount( 2 , $paths );
        $this->assertFileExists( $this->dest . '/a.png' );
        $this->assertFileExists( $this->dest . '/b.png' );
    }

    public function testReceiveUploadsMissingOrNotArrayThrows(): void
    {
        // single file (not an array) for the field -> rejected by receiveUploads
        $request = $this->request([ 'docs' => $this->uploadedFile( $this->pngSource , 'a.png' ) ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUploads( $request , 'docs' , $this->dest );
    }

    public function testReceiveUploadsRejectsNonFileEntry(): void
    {
        $request = $this->request([ 'docs' => [ $this->uploadedFile( $this->pngSource , 'a.png' ) , 'not-a-file' ] ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUploads( $request , 'docs' , $this->dest );
    }
}
