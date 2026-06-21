<?php

namespace tests\oihana\controllers\traits;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use ZipArchive;

use oihana\controllers\enums\FileResponseOption;
use oihana\controllers\traits\ArchiveTrait;
use oihana\enums\http\CacheControlDirective;
use oihana\enums\http\HttpHeader;
use oihana\files\enums\CompressionType;
use oihana\files\enums\FileMimeType;
use oihana\files\enums\ZipOption;
use oihana\files\exceptions\FileException;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ArchiveTraitTest extends TestCase
{
    private object $mock;

    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use ArchiveTrait;
        };

        $this->dir  = sys_get_temp_dir() . '/archive_trait_' . uniqid();
        mkdir( $this->dir );
        $this->file = $this->dir . '/hello.txt';
        file_put_contents( $this->file , 'hello world' );
    }

    protected function tearDown(): void
    {
        if ( !is_dir( $this->dir ) )
        {
            return ;
        }
        $it = new RecursiveIteratorIterator
        (
            new RecursiveDirectoryIterator( $this->dir , FilesystemIterator::SKIP_DOTS ) ,
            RecursiveIteratorIterator::CHILD_FIRST
        ) ;
        foreach ( $it as $entry )
        {
            $entry->isDir() ? @rmdir( $entry->getPathname() ) : @unlink( $entry->getPathname() ) ;
        }
        @rmdir( $this->dir );
    }

    /**
     * Builds a zip archive at $path from a [name => content] map.
     */
    private function makeZip( string $path , array $entries ): string
    {
        $zip = new ZipArchive() ;
        $zip->open( $path , ZipArchive::CREATE ) ;
        foreach ( $entries as $name => $content )
        {
            $zip->addFromString( $name , $content ) ;
        }
        $zip->close() ;
        return $path ;
    }

    /**
     * A passive Response stub with a writable body.
     */
    private function response(): ResponseInterface
    {
        $stream = $this->createStub( StreamInterface::class );
        $stream->method('write')->willReturnCallback( fn( $data ) => strlen( (string) $data ) );

        $response = $this->createStub( ResponseInterface::class );
        $response->method('getBody')->willReturn( $stream );
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnSelf();

        return $response;
    }

    /**
     * A Response stub recording the headers emitted via withHeader().
     */
    private function capturingResponse( array &$captured ): ResponseInterface
    {
        $stream = $this->createStub( StreamInterface::class );
        $stream->method('write')->willReturnCallback( fn( $data ) => strlen( (string) $data ) );

        $response = $this->createStub( ResponseInterface::class );
        $response->method('getBody')->willReturn( $stream );
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnCallback
        (
            function( $name , $value ) use ( &$captured , $response )
            {
                $captured[ $name ] = $value ;
                return $response ;
            }
        );

        return $response;
    }

    // ----------------------------------------------------------- zipResponse

    public function testZipResponseCreatesArchiveAndSetsHeaders(): void
    {
        $archive  = $this->dir . '/bundle.zip';
        $captured = [] ;
        $response = $this->capturingResponse( $captured );

        $result = $this->mock->zipResponse( null , $response , [ 'hello.txt' ] , $archive , $this->dir . '/' );

        $this->assertSame( $response , $result );
        $this->assertSame( FileMimeType::ZIP , $captured[ HttpHeader::CONTENT_TYPE ] );
        $this->assertSame( 'attachment; filename=bundle.zip' , $captured[ HttpHeader::CONTENT_DISPOSITION ] ); // basename, not full path
        $this->assertSame( CacheControlDirective::NO_CACHE , $captured[ HttpHeader::PRAGMA ] );
        $this->assertFileDoesNotExist( $archive ); // streamed then removed
    }

    public function testZipResponseFailsWhenAFileIsMissing(): void
    {
        $response = $this->response();

        // a listed file does not exist -> zip() throws FileException -> fail(500)
        $result = $this->mock->zipResponse
        (
            null ,
            $response ,
            [ 'does-not-exist.txt' ] ,
            $this->dir . '/bundle.zip' ,
            $this->dir . '/'
        );

        $this->assertSame( $response , $result );
    }

    // ----------------------------------------------------------- tarResponse

    public function testTarResponseGzipByDefault(): void
    {
        $captured = [] ;
        $response = $this->capturingResponse( $captured );
        $archive  = $this->dir . '/bundle.tar.gz';

        $result = $this->mock->tarResponse( null , $response , [ $this->file ] , $archive );

        $this->assertSame( $response , $result );
        $this->assertSame( FileMimeType::TAR_GZ , $captured[ HttpHeader::CONTENT_TYPE ] );
        $this->assertArrayHasKey( HttpHeader::CONTENT_LENGTH , $captured );
        $this->assertStringStartsWith( 'attachment;' , $captured[ HttpHeader::CONTENT_DISPOSITION ] );
        $this->assertSame( CacheControlDirective::NO_CACHE , $captured[ HttpHeader::PRAGMA ] );
        $this->assertFileDoesNotExist( $archive ); // temp archive removed after streaming
    }

    public function testTarResponseNone(): void
    {
        $captured = [] ;
        $response = $this->capturingResponse( $captured );

        $this->mock->tarResponse( null , $response , [ $this->file ] , $this->dir . '/plain.tar' , CompressionType::NONE );

        $this->assertSame( FileMimeType::TAR[ 1 ] , $captured[ HttpHeader::CONTENT_TYPE ] ); // application/x-tar
    }

    public function testTarResponseBzip2(): void
    {
        $captured = [] ;
        $response = $this->capturingResponse( $captured );

        $this->mock->tarResponse( null , $response , [ $this->file ] , $this->dir . '/bundle.tar.bz2' , CompressionType::BZIP2 );

        $this->assertSame( FileMimeType::TAR_BZ2 , $captured[ HttpHeader::CONTENT_TYPE ] ); // application/x-bzip2
    }

    public function testTarResponseWithDisabledHeadersAndCustomDisposition(): void
    {
        $captured = [] ;
        $response = $this->capturingResponse( $captured );

        $this->mock->tarResponse
        (
            null ,
            $response ,
            [ $this->file ] ,
            $this->dir . '/custom.tar.gz' ,
            CompressionType::GZIP ,
            [
                FileResponseOption::USE_CONTENT_TYPE    => false ,
                FileResponseOption::USE_CONTENT_LENGTH  => false ,
                FileResponseOption::CONTENT_DISPOSITION => 'inline; filename=custom.tar.gz' ,
            ]
        );

        $this->assertArrayNotHasKey( HttpHeader::CONTENT_TYPE   , $captured );
        $this->assertArrayNotHasKey( HttpHeader::CONTENT_LENGTH , $captured );
        $this->assertSame( 'inline; filename=custom.tar.gz' , $captured[ HttpHeader::CONTENT_DISPOSITION ] );
    }

    public function testTarResponseUnsupportedCompressionFails(): void
    {
        $response = $this->response();

        // ZIP is not a tar() compression -> UnsupportedCompressionException -> fail(500)
        $result = $this->mock->tarResponse
        (
            null ,
            $response ,
            [ $this->file ] ,
            $this->dir . '/bad.tar.zip' ,
            CompressionType::ZIP
        );

        $this->assertSame( $response , $result );
    }

    // ----------------------------------------------------------- extractTar

    public function testExtractTarRestoresFiles(): void
    {
        $archive = $this->dir . '/bundle.tar.gz';
        \oihana\files\archive\tar\tar( [ $this->file ] , $archive , CompressionType::GZIP );

        $out = $this->dir . '/out';
        $result = $this->mock->extractTar( $archive , $out );

        $this->assertTrue( $result );
        $this->assertSame( 'hello world' , file_get_contents( $out . '/hello.txt' ) );
    }

    // ----------------------------------------------------------- extractZip

    public function testExtractZipRestoresFiles(): void
    {
        $zip = $this->makeZip( $this->dir . '/bundle.zip' , [ 'a.txt' => 'AAA' , 'sub/b.txt' => 'BBB' ] );
        $out = $this->dir . '/out';

        $result = $this->mock->extractZip( $zip , $out );

        $this->assertTrue( $result );
        $this->assertSame( 'AAA' , file_get_contents( $out . '/a.txt' ) );
        $this->assertSame( 'BBB' , file_get_contents( $out . '/sub/b.txt' ) );
    }

    public function testExtractZipDryRunListsEntriesWithoutWriting(): void
    {
        $zip = $this->makeZip( $this->dir . '/bundle.zip' , [ 'a.txt' => 'AAA' , 'sub/b.txt' => 'BBB' ] );
        $out = $this->dir . '/out';

        $entries = $this->mock->extractZip( $zip , $out , [ ZipOption::DRY_RUN => true ] );

        $this->assertContains( 'a.txt' , $entries );
        $this->assertContains( 'sub/b.txt' , $entries );
        $this->assertFileDoesNotExist( $out . '/a.txt' ); // dry run: nothing written
    }

    public function testExtractZipRejectsZipSlip(): void
    {
        $zip = $this->makeZip( $this->dir . '/evil.zip' , [ '../evil.txt' => 'PWNED' , 'ok.txt' => 'OK' ] );

        $this->expectException( FileException::class );
        $this->mock->extractZip( $zip , $this->dir . '/out' );
    }

    public function testExtractZipRejectsTooManyEntries(): void
    {
        $zip = $this->makeZip( $this->dir . '/many.zip' , [ 'a.txt' => 'A' , 'b.txt' => 'B' , 'c.txt' => 'C' ] );

        $this->expectException( FileException::class );
        $this->mock->extractZip( $zip , $this->dir . '/out' , [ ZipOption::MAX_ENTRIES => 2 ] );
    }

    public function testExtractZipRejectsDecompressionBombBySize(): void
    {
        $zip = $this->makeZip( $this->dir . '/big.zip' , [ 'big.txt' => str_repeat( 'x' , 1000 ) ] );

        $this->expectException( FileException::class );
        $this->mock->extractZip( $zip , $this->dir . '/out' , [ ZipOption::MAX_SIZE => 100 ] );
    }

    public function testExtractZipOverwriteGuard(): void
    {
        $zip = $this->makeZip( $this->dir . '/b.zip' , [ 'a.txt' => 'NEW' ] );
        $out = $this->dir . '/out';
        mkdir( $out );
        file_put_contents( $out . '/a.txt' , 'OLD' );

        // overwrite explicitly disabled -> rejected
        try
        {
            $this->mock->extractZip( $zip , $out , [ ZipOption::OVERWRITE => false ] );
            $this->fail( 'Expected a FileException when a target exists and overwrite is disabled.' );
        }
        catch ( FileException ) {}

        // default (overwrite enabled, like untar) -> replaced
        $this->mock->extractZip( $zip , $out );
        $this->assertSame( 'NEW' , file_get_contents( $out . '/a.txt' ) );
    }

    public function testExtractZipInvalidArchiveThrows(): void
    {
        // $this->file is plain text, not a zip
        $this->expectException( FileException::class );
        $this->mock->extractZip( $this->file , $this->dir . '/out' );
    }

    public function testExtractZipCreatesDirectoryEntries(): void
    {
        $zipPath = $this->dir . '/dirs.zip';
        $zip = new ZipArchive() ;
        $zip->open( $zipPath , ZipArchive::CREATE ) ;
        $zip->addEmptyDir( 'emptydir' ) ;
        $zip->addFromString( 'emptydir/keep.txt' , 'K' ) ;
        $zip->close() ;

        $out = $this->dir . '/out';
        $result = $this->mock->extractZip( $zipPath , $out );

        $this->assertTrue( $result );
        $this->assertDirectoryExists( $out . '/emptydir' ); // directory entry handled
        $this->assertSame( 'K' , file_get_contents( $out . '/emptydir/keep.txt' ) );
    }
}
