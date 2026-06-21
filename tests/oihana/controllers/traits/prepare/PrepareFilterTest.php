<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareFilter;
use oihana\logging\LoggerTrait;
use Psr\Log\LoggerInterface;

final class PrepareFilterTest extends PrepareTestCase
{
    private function host(): object
    {
        $host = new class { use PrepareFilter, LoggerTrait; } ;
        $host->setLogger( $this->createStub( LoggerInterface::class ) ) ;
        return $host ;
    }

    private function prepare( object $host , $request , array $args , array &$params ): ?array
    {
        $ref = new \ReflectionMethod( $host , 'prepareFilter' ) ;
        $a   = [ $request , $args , &$params ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    public function testValidJsonFilterIsDecodedAndRegistered(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => '{"a":1}' ] ) , [] , $params ) ;

        $this->assertSame( [ 'a' => 1 ] , $result ) ;
        $this->assertSame( '{"a":1}' , $params[ ControllerParam::FILTER ] ) ;
    }

    public function testInvalidJsonWarnsAndFallsBackToArgs(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => 'not json' ] ) , [ ControllerParam::FILTER => [ 'fallback' => true ] ] , $params ) ;

        $this->assertSame( [ 'fallback' => true ] , $result ) ;
    }

    public function testValidJsonButNotArrayWarnsAndFallsBack(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => '42' ] ) , [] , $params ) ;

        $this->assertNull( $result ) ;
    }

    public function testNoRequestReturnsArgs(): void
    {
        $params = [] ;
        $this->assertSame( [ 'x' => 1 ] , $this->prepare( $this->host() , null , [ ControllerParam::FILTER => [ 'x' => 1 ] ] , $params ) ) ;
    }
}
