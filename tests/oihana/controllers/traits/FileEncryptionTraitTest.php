<?php

namespace tests\oihana\controllers\traits;

use RuntimeException;

use oihana\controllers\traits\FileEncryptionTrait;
use oihana\enums\http\HttpHeader;
use oihana\files\openssl\OpenSSLFileEncryption;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class FileEncryptionTraitTest extends TestCase
{
    private object $mock;

    private OpenSSLFileEncryption $enc;

    private string $dir;
    private string $plain;

    protected function setUp(): void
    {
        $this->enc = new OpenSSLFileEncryption( 'test-passphrase-123' );

        $this->mock = $this->host();
        $this->mock->initializeFileEncryption([ $this->mock::FILE_ENCRYPTION => $this->enc ]);

        $this->dir   = sys_get_temp_dir() . '/file_enc_' . uniqid();
        mkdir( $this->dir );
        $this->plain = $this->dir . '/secret.txt';
        file_put_contents( $this->plain , 'top secret content' );
    }

    protected function tearDown(): void
    {
        foreach ( glob( $this->dir . '/*' ) ?: [] as $f ) { @unlink( $f ); }
        @rmdir( $this->dir );
    }

    private function host(): object
    {
        return new class
        {
            use FileEncryptionTrait;
        };
    }

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

    // ----------------------------------------------------------- init

    public function testInitializeFromInitEnablesEncryption(): void
    {
        $encrypted = $this->mock->encryptFile( $this->plain );

        $this->assertFileExists( $encrypted );
        $this->assertTrue( $this->enc->isEncryptedFile( $encrypted ) );
    }

    public function testInitializeFromContainer(): void
    {
        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( $this->enc );

        $host = $this->host();
        $host->initializeFileEncryption( [] , $container );

        $encrypted = $host->encryptFile( $this->plain );
        $this->assertTrue( $this->enc->isEncryptedFile( $encrypted ) );
    }

    // ----------------------------------------------------------- round trip

    public function testEncryptThenDecryptRoundTrip(): void
    {
        $encrypted = $this->mock->encryptFile( $this->plain );
        $clear     = $this->mock->decryptFile( $encrypted , $this->dir . '/out.txt' );

        $this->assertSame( 'top secret content' , file_get_contents( $clear ) );
    }

    // ----------------------------------------------------------- responses

    public function testEncryptedFileResponse(): void
    {
        $captured = [] ;
        $response = $this->capturingResponse( $captured );

        $result = $this->mock->encryptedFileResponse( null , $response , $this->plain );

        $this->assertSame( $response , $result );
        $this->assertArrayHasKey( HttpHeader::CONTENT_TYPE , $captured );
        $this->assertFileDoesNotExist( $this->plain . '.enc' ); // temp encrypted file removed after streaming
    }

    public function testDecryptFileResponse(): void
    {
        $encrypted = $this->dir . '/doc.bin.enc';
        $this->enc->encrypt( $this->plain , $encrypted );

        $captured = [] ;
        $response = $this->capturingResponse( $captured );

        $result = $this->mock->decryptFileResponse( null , $response , $encrypted );

        $this->assertSame( $response , $result );
        $this->assertArrayHasKey( HttpHeader::CONTENT_TYPE , $captured );
        $this->assertFileDoesNotExist( $this->dir . '/doc.bin' ); // produced clear temp removed after streaming
    }

    public function testDecryptFileResponseFailureReturns500(): void
    {
        // the plaintext file is not a valid encrypted file -> decrypt() throws -> fail(500)
        $response = $this->response();

        $result = $this->mock->decryptFileResponse( null , $response , $this->plain );

        $this->assertSame( $response , $result );
    }

    // ----------------------------------------------------------- not configured

    public function testEncryptedFileResponseNotConfiguredReturns500(): void
    {
        $host     = $this->host(); // no initializeFileEncryption()
        $response = $this->response();

        $result = $host->encryptedFileResponse( null , $response , $this->plain );

        $this->assertSame( $response , $result );
    }

    public function testEncryptFileThrowsWhenNotConfigured(): void
    {
        $host = $this->host();

        $this->expectException( RuntimeException::class );
        $host->encryptFile( $this->plain );
    }
}
