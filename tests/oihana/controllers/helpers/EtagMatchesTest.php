<?php

namespace tests\oihana\controllers\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\controllers\helpers\etagMatches;

final class EtagMatchesTest extends TestCase
{
    public function testEmptyHeaderNeverMatches(): void
    {
        $this->assertFalse( etagMatches( '' , '"abc"' ) );
        $this->assertFalse( etagMatches( '   ' , '"abc"' ) );
    }

    public function testWildcardMatchesAnyEtag(): void
    {
        $this->assertTrue( etagMatches( '*' , '"abc"' ) );
    }

    public function testExactMatch(): void
    {
        $this->assertTrue( etagMatches( '"abc"' , '"abc"' ) );
    }

    public function testNoMatch(): void
    {
        $this->assertFalse( etagMatches( '"xyz"' , '"abc"' ) );
    }

    public function testMatchInList(): void
    {
        $this->assertTrue( etagMatches( '"abc", "def"' , '"def"' ) );
    }

    public function testWeakComparisonOnHeaderSide(): void
    {
        $this->assertTrue( etagMatches( 'W/"abc"' , '"abc"' ) );
    }

    public function testWeakComparisonOnEtagSide(): void
    {
        $this->assertTrue( etagMatches( '"abc"' , 'W/"abc"' ) );
    }
}
