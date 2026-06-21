<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\traits\prepare\PrepareBoolean;
use oihana\enums\Boolean;

final class PrepareBooleanTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class
        {
            use PrepareBoolean ;
            public bool $flag = false ;
            public function call( $request , array $args = [] , ?array &$params = null , ?string $name = 'flag' ): ?bool
            {
                return $this->prepareBoolean( $request , $args , $params , $name ) ;
            }
        } ;
    }

    public function testReturnsArgValueWithoutRequest(): void
    {
        $this->assertTrue( $this->host()->call( null , [ 'flag' => true ] ) ) ;
    }

    public function testReturnsPropertyDefaultWithoutRequestOrArg(): void
    {
        $this->assertFalse( $this->host()->call( null ) ) ;
    }

    public function testQueryParamTrueRegistersBooleanString(): void
    {
        $params = [] ;
        $result = $this->host()->call( $this->request( [ 'flag' => 'true' ] ) , [] , $params ) ;

        $this->assertTrue( $result ) ;
        $this->assertSame( Boolean::TRUE , $params['flag'] ) ;
    }

    public function testQueryParamFalseRegistersBooleanString(): void
    {
        $params = [] ;
        $result = $this->host()->call( $this->request( [ 'flag' => 'false' ] ) , [] , $params ) ;

        $this->assertFalse( $result ) ;
        $this->assertSame( Boolean::FALSE , $params['flag'] ) ;
    }

    public function testInvalidQueryParamFallsBackToProperty(): void
    {
        $host = $this->host() ;
        $host->flag = true ;
        $params = [] ;

        $result = $host->call( $this->request( [ 'flag' => 'notabool' ] ) , [] , $params ) ;

        $this->assertTrue( $result ) ; // FILTER_NULL_ON_FAILURE -> null -> property
    }
}
