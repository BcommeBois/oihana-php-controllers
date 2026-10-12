<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareFacets;
use oihana\exceptions\http\Error400;
use oihana\logging\LoggerTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

final class PrepareFacetsTest extends PrepareTestCase
{
    private function host(): object
    {
        $logger = $this->createMock( LoggerInterface::class ) ;
        $logger->expects( $this->never() )->method( $this->anything() ) ;

        $host = new class { use PrepareFacets, LoggerTrait; } ;
        $host->setLogger( $logger ) ;
        return $host ;
    }

    private function hostWithIdFacet(): object
    {
        $host = $this->host() ;
        $host->params = [ 'id' => ControllerParam::FACETS ] ; // ParamsTrait property
        return $host ;
    }

    private function prepare( object $host , $request , array $args , array &$params ): ?array
    {
        $ref = new \ReflectionMethod( $host , 'prepareFacets' ) ;
        $a   = [ $request , $args , &$params ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    private function assertRefused( object $host , array $query , string $message ): void
    {
        $params = [] ;
        try
        {
            $this->prepare( $host , $this->request( $query ) , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ;
            $this->fail( 'An unreadable facets parameter must be refused.' ) ;
        }
        catch ( Error400 $e )
        {
            $this->assertSame( 400 , $e->getCode() ) ;
            $this->assertSame( $message , $e->getMessage() ) ;
        }
        $this->assertSame( [] , $params ) ;
    }

    // ----------- ?facets=

    public function testValidJsonFacetsAreMergedAndRegistered(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FACETS => '{"price":"asc"}' ] ) , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ;

        $this->assertSame( [ 'seed' => 1 , 'price' => 'asc' ] , $result ) ;
        $this->assertSame( '%7B%22price%22%3A%22asc%22%7D' , $params[ ControllerParam::FACETS ] ) ;
    }

    public function testInvalidJsonFacetsAreRefusedWithError400(): void
    {
        $this->assertRefused( $this->host() , [ ControllerParam::FACETS => '{"price":"asc"' ] , 'The facets parameter is not valid JSON.' ) ;
    }

    public static function notArrays(): array
    {
        return
        [
            'int'    => [ '42'   , 'int'    ] ,
            'float'  => [ '1.5'  , 'float'  ] ,
            'string' => [ '"x"'  , 'string' ] ,
            'bool'   => [ 'true' , 'bool'   ] ,
        ];
    }

    #[DataProvider('notArrays')]
    public function testValidJsonButNotArrayFacetsAreRefusedWithError400( string $facets , string $type ): void
    {
        $this->assertRefused( $this->host() , [ ControllerParam::FACETS => $facets ] , 'The facets parameter must be a JSON object or list, ' . $type . ' given.' ) ;
    }

    public function testAbsentFacetsReturnArgsFacets(): void
    {
        $params = [] ;
        $this->assertSame( [ 'seed' => 1 ] , $this->prepare( $this->host() , $this->request() , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testEmptyFacetsMeanNoFacets(): void
    {
        $params = [] ;
        $this->assertSame( [ 'seed' => 1 ] , $this->prepare( $this->host() , $this->request( [ ControllerParam::FACETS => '' ] ) , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testNullFacetsMeanNoFacets(): void
    {
        $params = [] ;
        $this->assertSame( [ 'seed' => 1 ] , $this->prepare( $this->host() , $this->request( [ ControllerParam::FACETS => 'null' ] ) , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testNoRequestReturnsArgsFacets(): void
    {
        $params = [] ;
        $this->assertSame( [ 'a' => 1 ] , $this->prepare( $this->host() , null , [ ControllerParam::FACETS => [ 'a' => 1 ] ] , $params ) ) ;
    }

    // ----------- a parameter declared as a facet (?id=)

    public function testParamsFacetsInjectionFromDefinition(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->hostWithIdFacet() , $this->request( [ 'id' => '[1,2]' , ControllerParam::FACETS => '{}' ] ) , [] , $params ) ;

        $this->assertArrayHasKey( 'id' , $result ) ;
        $this->assertSame( [ 1 , 2 ] , $result['id'] ) ;
        $this->assertSame( '%5B1%2C2%5D' , $params[ 'id' ] ) ;
    }

    public function testParamsFacetsAcceptAJsonScalar(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->hostWithIdFacet() , $this->request( [ 'id' => '12' ] ) , [] , $params ) ;

        $this->assertSame( [ 'id' => 12 ] , $result ) ;
        $this->assertSame( '12' , $params[ 'id' ] ) ;
    }

    public static function unreadableIds(): array
    {
        return
        [
            'unquoted identifier' => [ 'abc'     ] ,
            'unclosed list'       => [ '[12,255' ] ,
        ];
    }

    #[DataProvider('unreadableIds')]
    public function testParamsFacetsThatAreNotJsonAreRefusedWithError400( string $id ): void
    {
        $this->assertRefused( $this->hostWithIdFacet() , [ 'id' => $id ] , 'The id parameter is not valid JSON.' ) ;
    }

    public function testEmptyParamsFacetMeansNoFacet(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->hostWithIdFacet() , $this->request( [ 'id' => '' ] ) , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ;

        $this->assertSame( [ 'seed' => 1 ] , $result ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testAbsentParamsFacetMeansNoFacet(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->hostWithIdFacet() , $this->request() , [ ControllerParam::FACETS => [ 'seed' => 1 ] ] , $params ) ;

        $this->assertSame( [ 'seed' => 1 ] , $result ) ;
        $this->assertSame( [] , $params ) ;
    }
}
