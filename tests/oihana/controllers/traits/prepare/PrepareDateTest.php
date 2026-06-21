<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\traits\prepare\PrepareDate;
use org\schema\constants\Prop;

final class PrepareDateTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class { use PrepareDate; public ?string $date = null; } ;
    }

    private function prepare( object $host , $request , array $args , array &$params ): ?string
    {
        $ref = new \ReflectionMethod( $host , 'prepareDate' ) ;
        $a   = [ $request , $args , &$params ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    public function testValidQueryDateIsUsedAndRegistered(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ Prop::DATE => '2024-01-15' ] ) , [] , $params ) ;

        $this->assertSame( '2024-01-15' , $result ) ;
        $this->assertSame( '2024-01-15' , $params[ Prop::DATE ] ) ;
    }

    public function testInvalidQueryDateFallsBackToToday(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ Prop::DATE => 'not-a-date' ] ) , [] , $params ) ;

        $this->assertSame( date( 'Y-m-d' ) , $result ) ;
        $this->assertArrayNotHasKey( Prop::DATE , $params ) ; // flag stays false
    }

    public function testWithoutRequestUsesArgOrToday(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , null , [ Prop::DATE => '2023-12-31' ] , $params ) ;
        $this->assertSame( '2023-12-31' , $result ) ;
    }
}
