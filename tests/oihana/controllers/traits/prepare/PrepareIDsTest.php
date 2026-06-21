<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareIDs;

final class PrepareIDsTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class
        {
            use PrepareIDs ;
            public function init( array $init ): void { $this->initializeIDs( $init ); }
            public function call( $request , array $args = [] , ?array &$params = [] ): ?string
            {
                return $this->preparedIDs( $request , $args , $params ) ;
            }
        } ;
    }

    public function testInitializeIDs(): void
    {
        $host = $this->host() ;
        $host->init( [ ControllerParam::IDS => '1,2,3' ] ) ;
        $this->assertSame( '1,2,3' , $host->ids ) ;
    }

    public function testArrayValueIsImploded(): void
    {
        $host = $this->host() ;
        $params = [] ;
        $this->assertSame( '1,2,3' , $host->call( null , [ ControllerParam::IDS => [ 1 , 2 , 3 ] ] , $params ) ) ;
    }

    public function testQueryParamOverridesAndRegisters(): void
    {
        $host = $this->host() ;
        $params = [] ;
        $result = $host->call( $this->request( [ ControllerParam::IDS => '7,8' ] ) , [] , $params ) ;

        $this->assertSame( '7,8' , $result ) ;
        $this->assertSame( '7,8' , $params[ ControllerParam::IDS ] ) ;
    }
}
