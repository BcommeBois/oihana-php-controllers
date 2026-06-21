<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareFacets;
use oihana\logging\LoggerTrait;
use Psr\Log\LoggerInterface;

final class PrepareFacetsTest extends PrepareTestCase
{
    private function host(): object
    {
        $host = new class { use PrepareFacets, LoggerTrait; } ;
        $host->setLogger( $this->createStub( LoggerInterface::class ) ) ;
        return $host ;
    }

    private function prepare( object $host , $request , array $args , array &$params ): ?array
    {
        $ref = new \ReflectionMethod( $host , 'prepareFacets' ) ;
        $a   = [ $request , $args , &$params ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    public function testValidJsonFacetsAreMergedAndRegistered(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FACETS => '{"price":"asc"}' ] ) , [] , $params ) ;

        $this->assertSame( [ 'price' => 'asc' ] , $result ) ;
        $this->assertArrayHasKey( ControllerParam::FACETS , $params ) ;
    }

    public function testInvalidJsonFacetsWarnAndKeepArgs(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FACETS => 'not json' ] ) , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ;

        $this->assertSame( [ 'seed' => 1 ] , $result ) ;
    }

    public function testNoRequestReturnsArgsFacets(): void
    {
        $params = [] ;
        $this->assertSame( [ 'a' => 1 ] , $this->prepare( $this->host() , null , [ ControllerParam::FACETS => [ 'a' => 1 ] ] , $params ) ) ;
    }

    public function testParamsFacetsInjectionFromDefinition(): void
    {
        $host = $this->host() ;
        $host->params = [ 'id' => ControllerParam::FACETS ] ; // ParamsTrait property

        $params = [] ;
        $result = $this->prepare( $host , $this->request( [ 'id' => '[1,2]' , ControllerParam::FACETS => '{}' ] ) , [] , $params ) ;

        $this->assertArrayHasKey( 'id' , $result ) ;
        $this->assertSame( [ 1 , 2 ] , $result['id'] ) ;
    }
}
