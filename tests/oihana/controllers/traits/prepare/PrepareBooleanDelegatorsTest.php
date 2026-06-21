<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareBench;
use oihana\controllers\traits\prepare\PrepareHasTotal;
use oihana\controllers\traits\prepare\PrepareMargin;
use oihana\controllers\traits\prepare\PrepareMock;
use oihana\enums\Boolean;

/**
 * Bench / Mock / Margin / HasTotal all delegate to prepareBoolean.
 */
final class PrepareBooleanDelegatorsTest extends PrepareTestCase
{
    public function testPrepareBench(): void
    {
        $host = new class { use PrepareBench; public bool $bench = false; } ;
        $params = [] ;
        $this->assertTrue( $this->invoke( $host , 'prepareBench' , $this->request( [ ControllerParam::BENCH => 'true' ] ) , $params ) ) ;
        $this->assertSame( Boolean::TRUE , $params[ ControllerParam::BENCH ] ) ;
    }

    public function testPrepareMock(): void
    {
        // PrepareMock calls prepareBoolean() but does not compose PrepareBoolean
        // itself; a real controller mixes both, so the test host does too.
        $host = new class { use PrepareMock, \oihana\controllers\traits\prepare\PrepareBoolean; } ;
        $params = [] ;
        $this->assertTrue( $this->invoke( $host , 'prepareMock' , $this->request( [ ControllerParam::MOCK => 'true' ] ) , $params ) ) ;
        $this->assertSame( Boolean::TRUE , $params[ ControllerParam::MOCK ] ) ;
    }

    public function testPrepareMargin(): void
    {
        $host = new class { use PrepareMargin; public bool $margin = false; } ;
        $params = [] ;
        $this->assertFalse( $this->invoke( $host , 'prepareMargin' , $this->request( [ ControllerParam::MARGIN => 'false' ] ) , $params ) ) ;
        $this->assertSame( Boolean::FALSE , $params[ ControllerParam::MARGIN ] ) ;
    }

    public function testPrepareHasTotalAndInitialize(): void
    {
        $host = new class { use PrepareHasTotal; } ;

        $this->assertTrue( $host->hasTotal ) ;
        $host->initializeHasTotal( [ ControllerParam::HAS_TOTAL => false ] ) ;
        $this->assertFalse( $host->hasTotal ) ;

        $params = [] ;
        $this->assertTrue( $host->prepareHasTotal( $this->request( [ ControllerParam::HAS_TOTAL => 'true' ] ) , [] , $params ) ) ;
        $this->assertSame( Boolean::TRUE , $params[ ControllerParam::HAS_TOTAL ] ) ;
    }

    private function invoke( object $host , string $method , $request , array &$params ): ?bool
    {
        $ref = new \ReflectionMethod( $host , $method ) ;
        $args = [ $request , [] , &$params ] ;
        return $ref->invokeArgs( $host , $args ) ;
    }
}
