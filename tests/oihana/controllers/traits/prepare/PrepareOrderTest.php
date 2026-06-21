<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareOrder;

final class PrepareOrderTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class
        {
            use PrepareOrder ;
            public function setApi( array $api ): void { $this->api = $api ; }
            public function call( $request , ?array &$params , &$order ): void
            {
                $this->prepareOrder( $request , $params , $order ) ;
            }
        } ;
    }

    public function testValidOrderIsUppercasedAndRegistered(): void
    {
        $host = $this->host() ;
        $host->setApi( [ ControllerParam::ORDERS => [ 'ASC' , 'DESC' ] ] ) ;

        $params = [] ;
        $order  = 'ASC' ;
        $host->call( $this->request( [ ControllerParam::ORDER => 'desc' ] ) , $params , $order ) ;

        $this->assertSame( 'DESC' , $order ) ;
        $this->assertSame( 'DESC' , $params[ ControllerParam::ORDER ] ) ;
    }

    public function testInvalidOrderKeepsCurrentButStillRegisters(): void
    {
        $host = $this->host() ;
        $host->setApi( [ ControllerParam::ORDERS => [ 'ASC' , 'DESC' ] ] ) ;

        $params = [] ;
        $order  = 'ASC' ;
        $host->call( $this->request( [ ControllerParam::ORDER => 'sideways' ] ) , $params , $order ) ;

        $this->assertSame( 'ASC' , $order ) ;
        $this->assertSame( 'ASC' , $params[ ControllerParam::ORDER ] ) ;
    }

    public function testNoRequestLeavesOrderUntouched(): void
    {
        $params = [] ;
        $order  = 'ASC' ;
        $this->host()->call( null , $params , $order ) ;

        $this->assertSame( 'ASC' , $order ) ;
        $this->assertArrayNotHasKey( ControllerParam::ORDER , $params ) ;
    }
}
