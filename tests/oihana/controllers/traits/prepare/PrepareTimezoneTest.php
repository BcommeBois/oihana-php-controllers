<?php

namespace tests\oihana\controllers\traits\prepare;

use DateTimeZone;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareTimezone;

final class PrepareTimezoneTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class
        {
            use PrepareTimezone ;
            public function call( $request , ?array &$params , ?DateTimeZone &$tz , ?array $opts , string $default = 'Europe/Paris' ): void
            {
                $this->prepareTimezone( $request , $params , $tz , $opts , $default ) ;
            }
        } ;
    }

    public function testUsesRequestTimezone(): void
    {
        $params = [] ;
        $tz     = null ;
        $this->host()->call( $this->request( [ ControllerParam::TIMEZONE => 'UTC' ] ) , $params , $tz , [] ) ;

        $this->assertInstanceOf( DateTimeZone::class , $tz ) ;
        $this->assertSame( 'UTC' , $tz->getName() ) ;
        $this->assertSame( $tz , $params[ ControllerParam::TIMEZONE ] ) ;
    }

    public function testFallsBackToDefault(): void
    {
        $params = [] ;
        $tz     = null ;
        $this->host()->call( $this->request( [] ) , $params , $tz , [] , 'Europe/Lisbon' ) ;

        $this->assertSame( 'Europe/Lisbon' , $tz->getName() ) ;
    }

    public function testNoRequestDoesNothing(): void
    {
        $params = [] ;
        $tz     = null ;
        $this->host()->call( null , $params , $tz , [] ) ;

        $this->assertNull( $tz ) ;
    }
}
