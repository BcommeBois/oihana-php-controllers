<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareActive;

final class PrepareActiveTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class
        {
            use PrepareActive ;
            public function call( $request , array $args = [] , bool $default = true ): ?bool
            {
                return $this->prepareActive( $request , $args , $default ) ;
            }
        } ;
    }

    public function testDefaultWithoutRequest(): void
    {
        $this->assertTrue( $this->host()->call( null ) ) ;
        $this->assertFalse( $this->host()->call( null , [ ControllerParam::ACTIVE => false ] ) ) ;
    }

    public function testFalseQueryValuesDisableActive(): void
    {
        foreach ( [ '0' , 'false' , 'FALSE' ] as $value )
        {
            $this->assertFalse( $this->host()->call( $this->request( [ ControllerParam::ACTIVE => $value ] ) ) ) ;
        }
    }

    public function testOtherQueryValueKeepsDefault(): void
    {
        $this->assertTrue( $this->host()->call( $this->request( [ ControllerParam::ACTIVE => '1' ] ) ) ) ;
    }
}
