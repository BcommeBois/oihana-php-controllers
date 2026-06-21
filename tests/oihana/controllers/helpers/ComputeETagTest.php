<?php

namespace tests\oihana\controllers\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\controllers\helpers\computeETag;

final class ComputeETagTest extends TestCase
{
    private string $dir;
    private string $file;

    protected function setUp(): void
    {
        $this->dir  = sys_get_temp_dir() . '/compute_etag_' . uniqid();
        mkdir( $this->dir );
        $this->file = $this->dir . '/hello.txt';
        file_put_contents( $this->file , 'hello world' );
    }

    protected function tearDown(): void
    {
        @unlink( $this->file );
        @rmdir( $this->dir );
    }

    public function testMetadataStrongByDefault(): void
    {
        $expected = '"' . sprintf( '%x-%x' , filemtime( $this->file ) , filesize( $this->file ) ) . '"' ;
        $this->assertSame( $expected , computeETag( $this->file ) );
    }

    public function testWeakMetadata(): void
    {
        $expected = 'W/"' . sprintf( '%x-%x' , filemtime( $this->file ) , filesize( $this->file ) ) . '"' ;
        $this->assertSame( $expected , computeETag( $this->file , weak: true ) );
    }

    public function testStrongContentHash(): void
    {
        $expected = '"' . md5_file( $this->file ) . '"' ;
        $this->assertSame( $expected , computeETag( $this->file , hashContent: true ) );
    }

    public function testWeakContentHash(): void
    {
        $expected = 'W/"' . md5_file( $this->file ) . '"' ;
        $this->assertSame( $expected , computeETag( $this->file , weak: true , hashContent: true ) );
    }
}
