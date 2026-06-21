<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareInt;
use oihana\controllers\traits\prepare\PrepareQuantity;

final class PrepareIntTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class
        {
            use PrepareInt ;
            public ?int $count = null ;
            public function call( $request , array $args = [] , ?array &$params = null , ?int $default = null , ?string $name = 'count' ): ?int
            {
                return $this->prepareInt( $request , $args , $params , $default , $name ) ;
            }
        } ;
    }

    public function testReturnsArgValueWithoutRequest(): void
    {
        $this->assertSame( 7 , $this->host()->call( null , [ 'count' => 7 ] ) ) ;
    }

    public function testDefaultWhenNotAnInt(): void
    {
        $this->assertSame( 5 , $this->host()->call( null , [] , $params , 5 ) ) ;
    }

    public function testQueryParamIsParsedAndRegistered(): void
    {
        $params = [] ;
        $result = $this->host()->call( $this->request( [ 'count' => '42' ] ) , [] , $params ) ;

        $this->assertSame( 42 , $result ) ;
        $this->assertSame( 42 , $params['count'] ) ;
    }

    public function testInvalidQueryParamFallsBackToDefault(): void
    {
        $params = [] ;
        $result = $this->host()->call( $this->request( [ 'count' => 'abc' ] ) , [] , $params , 9 ) ;

        $this->assertSame( 9 , $result ) ;
        $this->assertSame( 9 , $params['count'] ) ;
    }

    public function testPrepareQuantityDelegates(): void
    {
        $host = new class { use PrepareQuantity; public ?int $quantity = null; } ;
        $params = [] ;

        $ref  = new \ReflectionMethod( $host , 'prepareQuantity' ) ;
        $args = [ $this->request( [ ControllerParam::QUANTITY => '3' ] ) , [] , &$params , null ] ;

        $this->assertSame( 3 , $ref->invokeArgs( $host , $args ) ) ;
        $this->assertSame( 3 , $params[ ControllerParam::QUANTITY ] ) ;
    }
}
