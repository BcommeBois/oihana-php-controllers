<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\FilterOption;
use oihana\controllers\traits\prepare\PrepareInterval;

final class PrepareIntervalTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class { use PrepareInterval; } ;
    }

    private function prepare( object $host , $request , array &$params , ?string &$interval , ?array $timeOptions ): void
    {
        $ref = new \ReflectionMethod( $host , 'prepareInterval' ) ;
        $a   = [ $request , &$params , &$interval , $timeOptions ] ;
        $ref->invokeArgs( $host , $a ) ;
    }

    public function testValidIntervalIsClampedAndRegistered(): void
    {
        $params   = [] ;
        $interval = null ;
        $this->prepare( $this->host() , $this->request( [ ControllerParam::INTERVAL => '5' ] ) , $params , $interval , [ FilterOption::MAX_RANGE => 10 , ControllerParam::INTERVAL_DEFAULT => 1 ] ) ;

        $this->assertSame( 5 , $interval ) ;
        $this->assertSame( 5 , $params[ ControllerParam::INTERVAL ] ) ;
    }

    public function testInvalidIntervalFallsBackToDefault(): void
    {
        $params   = [] ;
        $interval = null ;
        $this->prepare( $this->host() , $this->request( [ ControllerParam::INTERVAL => 'xxx' ] ) , $params , $interval , [ FilterOption::MAX_RANGE => 10 , ControllerParam::INTERVAL_DEFAULT => 3 ] ) ;

        $this->assertSame( 3 , $interval ) ;
    }

    public function testNoRequestUsesDefault(): void
    {
        $params   = [] ;
        $interval = null ;
        $this->prepare( $this->host() , null , $params , $interval , [ ControllerParam::INTERVAL_DEFAULT => 7 ] ) ;

        $this->assertSame( 7 , $interval ) ;
    }
}
