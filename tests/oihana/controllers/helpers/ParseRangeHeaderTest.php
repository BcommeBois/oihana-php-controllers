<?php

namespace tests\oihana\controllers\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\controllers\helpers\parseRangeHeader;

final class ParseRangeHeaderTest extends TestCase
{
    private const int SIZE = 11 ; // "hello world"

    public function testNoOrNonByteRangeReturnsNull(): void
    {
        $this->assertNull( parseRangeHeader( '' , self::SIZE ) );
        $this->assertNull( parseRangeHeader( 'items=0-1' , self::SIZE ) );
    }

    public function testMalformedOrMultiRangeReturnsNull(): void
    {
        $this->assertNull( parseRangeHeader( 'bytes=abc' , self::SIZE ) );        // no dash
        $this->assertNull( parseRangeHeader( 'bytes=0-9,20-29' , self::SIZE ) );  // multi-range
        $this->assertNull( parseRangeHeader( 'bytes=x-5' , self::SIZE ) );        // non-digit start
        $this->assertNull( parseRangeHeader( 'bytes=0-x' , self::SIZE ) );        // non-digit end
        $this->assertNull( parseRangeHeader( 'bytes=-' , self::SIZE ) );          // empty suffix
    }

    public function testClosedRange(): void
    {
        $this->assertSame( [ 0 , 4 ] , parseRangeHeader( 'bytes=0-4' , self::SIZE ) );
    }

    public function testOpenEndedRange(): void
    {
        $this->assertSame( [ 5 , 10 ] , parseRangeHeader( 'bytes=5-' , self::SIZE ) );
    }

    public function testSuffixRange(): void
    {
        $this->assertSame( [ 7 , 10 ] , parseRangeHeader( 'bytes=-4' , self::SIZE ) );
    }

    public function testEndClampedToFileSize(): void
    {
        $this->assertSame( [ 0 , 10 ] , parseRangeHeader( 'bytes=0-99999' , self::SIZE ) );
    }

    public function testUnsatisfiableReturnsFalse(): void
    {
        $this->assertFalse( parseRangeHeader( 'bytes=99999-' , self::SIZE ) ); // start past end
        $this->assertFalse( parseRangeHeader( 'bytes=-0' , self::SIZE ) );     // zero-length suffix
        $this->assertFalse( parseRangeHeader( 'bytes=0-0' , 0 ) );             // empty file
    }
}
