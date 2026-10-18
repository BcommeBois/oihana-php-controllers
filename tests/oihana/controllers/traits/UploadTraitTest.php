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
    private string $binSource;
    private string $jpegSource;
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

        $this->jpegSource = $this->dir . '/source.jpg';
        $image = new Imagick();
        $image->newImage( 2 , 2 , new ImagickPixel( 'blue' ) );
        $image->setImageFormat( 'jpeg' );
        $image->writeImage( $this->jpegSource );
        $image->clear();

        // Random bytes : a content finfo does not recognize (application/octet-stream).
        $this->binSource = $this->dir . '/source.bin';
        file_put_contents( $this->binSource , "\x00\x01" . random_bytes( 254 ) );
    }

    protected function tearDown(): void
    {
        @chmod( $this->dest , 0700 );
        $this->removeTree( $this->dir );
    }

    private function removeTree( string $path ): void
    {
        if ( is_dir( $path ) && !is_link( $path ) )
        {
            foreach ( array_diff( scandir( $path ) ?: [] , [ '.' , '..' ] ) as $entry )
            {
                $this->removeTree( $path . '/' . $entry );
            }
            @rmdir( $path );
            return;
        }
        @unlink( $path );
    }

    /**
     * Every entry of the destination directory, hidden ones included.
     *
     * @return string[]
     */
    private function stored(): array
    {
        return array_values( array_diff( scandir( $this->dest ) ?: [] , [ '.' , '..' ] ) );
    }

    /**
     * Builds an UploadedFileInterface stub whose moveTo() copies $source to the target.
     */
    private function uploadedFile( string $source , ?string $clientName = null , int $error = UPLOAD_ERR_OK , ?int $size = null , ?callable $afterMove = null ): UploadedFileInterface
    {
        $file = $this->createStub( UploadedFileInterface::class );
        $file->method('getError')->willReturn( $error );
        $file->method('getSize')->willReturn( $size ?? ( is_file( $source ) ? filesize( $source ) : 0 ) );
        $file->method('getClientFilename')->willReturn( $clientName ?? basename( $source ) );
        $file->method('moveTo')->willReturnCallback( function( $target ) use ( $source , $afterMove )
        {
            @copy( $source , $target );
            if ( $afterMove !== null )
            {
                $afterMove( $target );
            }
        });
        return $file;
    }

    private function request( array $uploadedFiles ): Request
    {
        $request = $this->createStub( Request::class );
        $request->method('getUploadedFiles')->willReturn( $uploadedFiles );
        return $request;
    }

    // ----------------------------------------------------------- receiveUpload

    /**
     * By default the file gets a random name and the extension of the type read from its content,
     * never the name the client chose.
     */
    public function testReceiveUploadStoresFileUnderARandomName(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest );

        $this->assertSame( $this->dest , dirname( $path ) );
        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.png$/' , basename( $path ) );
        $this->assertFileEquals( $this->pngSource , $path );
    }

    /**
     * 🚨 The reason the default name changed : a script holding a real image passes the type
     * check, and kept its name it would be stored — and served — as `photo.php`.
     */
    public function testAScriptHoldingAnImageIsStoredWithTheExtensionOfItsType(): void
    {
        $default = $this->mock->receiveUpload( $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.php' ) ]) , 'avatar' , $this->dest );
        $client  = $this->mock->receiveUpload( $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.php' ) ]) , 'avatar' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );

        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.png$/' , basename( $default ) );
        $this->assertSame( $this->dest . '/photo.png' , $client );
    }

    public function testNoProvisionalFileIsLeftAfterAStoredUpload(): void
    {
        $path = $this->mock->receiveUpload( $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource ) ]) , 'avatar' , $this->dest );

        $this->assertSame( [ basename( $path ) ] , $this->stored() );
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
            $this->assertSame( [] , $this->stored() , 'the rejected file must leave nothing, not even its provisional copy' );
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

    public function testReceiveUploadEmptyFilenameThrows(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        $this->expectException( FileException::class );
        $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::FILENAME => '' ] );
    }

    /**
     * An empty client name no longer matters : the default name is drawn by the trait.
     */
    public function testAnEmptyClientNameStillGetsARandomName(): void
    {
        $path = $this->mock->receiveUpload( $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , '' ) ]) , 'avatar' , $this->dest );

        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.png$/' , basename( $path ) );
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

        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );

        $this->assertSame( $this->dest . '/evil.png' , $path ); // path components stripped
    }

    /**
     * A client name is cleaned : no leading dot (a `.user.ini` would configure PHP for the whole
     * directory), no dot inside (`photo.php.png` is run as PHP by some servers), plain ASCII.
     */
    public function testClientFilenameIsCleaned(): void
    {
        $cases =
        [
            [ $this->pngSource  , 'Façade Nord (2).PNG' , 'facade-nord-2.png' ] ,
            [ $this->pngSource  , 'IMG_0001'            , 'img-0001.png'      ] ,
            [ $this->txtSource  , '.user.ini'           , 'user.txt'          ] ,
            [ $this->pngSource  , 'photo.php.png'       , 'photo-php.png'     ] ,
        ];

        foreach ( $cases as [ $source , $clientName , $expected ] )
        {
            $path = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $source , $clientName ) ]) , 'f' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );

            $this->assertSame( $this->dest . '/' . $expected , $path , $clientName );
        }
    }

    public function testAClientNameWithNothingLeftGetsARandomName(): void
    {
        foreach ( [ '日本語.png' , '___.png' , '..htaccess' ] as $clientName )
        {
            $path = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->pngSource , $clientName ) ]) , 'f' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );

            $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.png$/' , basename( $path ) , $clientName );
        }
    }

    /**
     * When the type has several extensions, the client's is kept if it is one of them.
     */
    public function testTheExtensionOfTheClientIsKeptWhenItMatches(): void
    {
        $jpg  = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->jpegSource , 'facade.jpg'  ) ]) , 'f' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );
        $jpeg = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->jpegSource , 'random.jpeg' ) ]) , 'f' , $this->dest );
        $none = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->jpegSource , 'random.gif'  ) ]) , 'f' , $this->dest );

        $this->assertSame( $this->dest . '/facade.jpg' , $jpg );
        $this->assertStringEndsWith( '.jpeg' , $jpeg );
        $this->assertStringEndsWith( '.jpeg' , $none , 'An extension of another type is replaced by the first one of the detected type.' );
    }

    public function testAClientNameAlreadyTakenIsNumbered(): void
    {
        file_put_contents( $this->dest . '/img-0001.png' , 'existing' );

        $request = $this->request
        ([
            'photos' =>
            [
                $this->uploadedFile( $this->pngSource , 'IMG_0001.png' ) ,
                $this->uploadedFile( $this->pngSource , 'IMG_0001.png' ) ,
            ]
        ]);

        $paths = $this->mock->receiveUploads( $request , 'photos' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );

        $this->assertSame( [ $this->dest . '/img-0001-2.png' , $this->dest . '/img-0001-3.png' ] , $paths );
        $this->assertStringEqualsFile( $this->dest . '/img-0001.png' , 'existing' );
    }

    public function testAClientNameNumberedTooOftenGetsARandomName(): void
    {
        file_put_contents( $this->dest . '/photo.png' , 'existing' );
        for ( $number = 2 ; $number <= 100 ; $number++ )
        {
            file_put_contents( $this->dest . '/photo-' . $number . '.png' , 'existing' );
        }

        $path = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]) , 'f' , $this->dest , [ UploadOption::CLIENT_FILENAME => true ] );

        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.png$/' , basename( $path ) );
    }

    public function testAClientNameOverwritesWhenAsked(): void
    {
        file_put_contents( $this->dest . '/photo.png' , 'existing' );

        $options = [ UploadOption::CLIENT_FILENAME => true , UploadOption::OVERWRITE => true ];
        $path    = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]) , 'f' , $this->dest , $options );
        $fresh   = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->pngSource , 'other.png' ) ]) , 'f' , $this->dest , $options );

        $this->assertSame( $this->dest . '/photo.png' , $path );
        $this->assertFileEquals( $this->pngSource , $path );
        $this->assertSame( $this->dest . '/other.png' , $fresh );
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
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'whatever.png' ) ]);

        // without overwrite -> rejected, the existing file untouched, nothing left behind
        try
        {
            $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::FILENAME => 'photo.png' ] );
            $this->fail( 'Expected a FileException when the target exists and overwrite is false.' );
        }
        catch ( FileException )
        {
            $this->assertStringEqualsFile( $this->dest . '/photo.png' , 'existing' );
            $this->assertSame( [ 'photo.png' ] , $this->stored() );
        }

        // with overwrite -> stored
        $path = $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::FILENAME => 'photo.png' , UploadOption::OVERWRITE => true ] );
        $this->assertFileEquals( $this->pngSource , $path );
    }

    /**
     * A name the controller chose is kept or refused, never altered.
     */
    public function testAFilenameThatLiesAboutItsTypeIsRefused(): void
    {
        $request = $this->request([ 'avatar' => $this->uploadedFile( $this->pngSource , 'photo.png' ) ]);

        try
        {
            $this->mock->receiveUpload( $request , 'avatar' , $this->dest , [ UploadOption::FILENAME => 'avatar.php' ] );
            $this->fail( 'A .php name for a PNG content must be refused.' );
        }
        catch ( FileException $e )
        {
            $this->assertStringContainsString( 'does not match the type' , $e->getMessage() );
            $this->assertSame( [] , $this->stored() );
        }
    }

    public function testAFilenameStartingWithADotIsRefused(): void
    {
        $request = $this->request([ 'f' => $this->uploadedFile( $this->txtSource , 'note.txt' ) ]);

        try
        {
            $this->mock->receiveUpload( $request , 'f' , $this->dest , [ UploadOption::FILENAME => '.htaccess' ] );
            $this->fail( 'A name starting with a dot must be refused.' );
        }
        catch ( FileException )
        {
            $this->assertSame( [] , $this->stored() , 'refused before anything is moved' );
        }
    }

    public function testAFilenameWithoutExtensionOrWithAMatchingOneIsKept(): void
    {
        $bare = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->pngSource ) ]) , 'f' , $this->dest , [ UploadOption::FILENAME => 'avatar' ] );
        $jpg  = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->jpegSource ) ]) , 'f' , $this->dest , [ UploadOption::FILENAME => 'facade.JPG' ] );

        $this->assertSame( $this->dest . '/avatar' , $bare );
        $this->assertSame( $this->dest . '/facade.JPG' , $jpg );
    }

    // ----------------------------------------------------------- extensions

    public function testAnUnrecognizedContentGetsNoExtension(): void
    {
        $path = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->binSource , 'data.app' ) ]) , 'f' , $this->dest );

        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/' , basename( $path ) );
    }

    public function testTheExtensionsOptionFillsAGapAndWinsOverTheCatalogue(): void
    {
        $bin = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->binSource ) ]) , 'f' , $this->dest , [ UploadOption::EXTENSIONS => [ 'application/octet-stream' => '.bin' ] ] );
        $jpg = $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->jpegSource ) ]) , 'f' , $this->dest , [ UploadOption::EXTENSIONS => [ 'Image/JPEG' => '.JPG' ] ] );

        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.bin$/' , basename( $bin ) );
        $this->assertMatchesRegularExpression( '/^[0-9a-f]{32}\.jpg$/' , basename( $jpg ) );
    }

    public function testAMalformedExtensionIsRefused(): void
    {
        foreach ( [ [ 'image/png' => '.php.png' ] , [ 'image/png' => 'png' ] , [ 'image/png' => '../x' ] , 'not-an-array' ] as $extensions )
        {
            try
            {
                $this->mock->receiveUpload( $this->request([ 'f' => $this->uploadedFile( $this->pngSource ) ]) , 'f' , $this->dest , [ UploadOption::EXTENSIONS => $extensions ] );
                $this->fail( 'Expected a FileException for ' . json_encode( $extensions ) );
            }
            catch ( FileException )
            {
                $this->assertSame( [] , $this->stored() , 'refused before anything is moved' );
            }
        }
    }

    // ----------------------------------------------------------- the destination fails

    /**
     * A directory that refuses to be written once the file is in : no name can be reserved.
     */
    public function testANameThatCannotBeReservedIsReported(): void
    {
        $dest    = $this->dest;
        $request = $this->request([ 'f' => $this->uploadedFile( $this->pngSource , afterMove: static fn() => chmod( $dest , 0500 ) ) ]);

        $this->expectException( FileException::class );
        $this->expectExceptionMessage( 'could not be stored in' );

        $this->mock->receiveUpload( $request , 'f' , $this->dest );
    }

    /**
     * A provisional file gone before its rename — nothing to detect a type from, nothing to store.
     */
    public function testAFileGoneBeforeItsRenameIsReported(): void
    {
        $request = $this->request([ 'f' => $this->uploadedFile( $this->pngSource , afterMove: static fn( string $target ) => unlink( $target ) ) ]);

        try
        {
            $this->mock->receiveUpload( $request , 'f' , $this->dest );
            $this->fail( 'A vanished provisional file must be reported.' );
        }
        catch ( FileException $e )
        {
            $this->assertStringContainsString( 'could not be stored as' , $e->getMessage() );
            $this->assertSame( [] , $this->stored() , 'the reservation is removed' );
        }
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
        $this->assertNotSame( $paths[ 0 ] , $paths[ 1 ] );
        $this->assertFileExists( $paths[ 0 ] );
        $this->assertFileExists( $paths[ 1 ] );
    }

    /**
     * Two phones send two `IMG_0001.png` : two files, not a refusal.
     */
    public function testReceiveUploadsStoresTwoFilesOfTheSameClientName(): void
    {
        $request = $this->request
        ([
            'docs' =>
            [
                $this->uploadedFile( $this->pngSource , 'IMG_0001.png' ) ,
                $this->uploadedFile( $this->pngSource , 'IMG_0001.png' ) ,
            ]
        ]);

        $paths = $this->mock->receiveUploads( $request , 'docs' , $this->dest , [ UploadOption::FILENAME => 'ignored.png' ] );

        $this->assertCount( 2 , array_unique( $paths ) );
        $this->assertCount( 2 , $this->stored() );
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
