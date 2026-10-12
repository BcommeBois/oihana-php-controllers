<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareFilter;
use oihana\exceptions\http\Error400;
use oihana\logging\LoggerTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

final class PrepareFilterTest extends PrepareTestCase
{
    private function host(): object
    {
        $logger = $this->createMock( LoggerInterface::class ) ;
        $logger->expects( $this->never() )->method( $this->anything() ) ;

        $host = new class { use PrepareFilter, LoggerTrait; } ;
        $host->setLogger( $logger ) ;
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

    public function testValidJsonListFilterIsDecodedAndRegistered(): void
    {
        $params = [] ;
        $result = $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => '[1,2]' ] ) , [] , $params ) ;

        $this->assertSame( [ 1 , 2 ] , $result ) ;
        $this->assertSame( '[1,2]' , $params[ ControllerParam::FILTER ] ) ;
    }

    public function testInvalidJsonIsRefusedWithError400(): void
    {
        $params = [] ;
        try
        {
            $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => '{"key":"age","val":30' ] ) , [ ControllerParam::FILTER => [ 'fallback' => true ] ] , $params ) ;
            $this->fail( 'An unreadable filter must be refused.' ) ;
        }
        catch ( Error400 $e )
        {
            $this->assertSame( 400 , $e->getCode() ) ;
            $this->assertSame( 'The filter parameter is not valid JSON.' , $e->getMessage() ) ;
        }
        $this->assertSame( [] , $params ) ;
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
    public function testValidJsonButNotArrayIsRefusedWithError400( string $filter , string $type ): void
    {
        $params = [] ;
        try
        {
            $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => $filter ] ) , [ ControllerParam::FILTER => [ 'fallback' => true ] ] , $params ) ;
            $this->fail( 'A filter that is not a JSON object or list must be refused.' ) ;
        }
        catch ( Error400 $e )
        {
            $this->assertSame( 400 , $e->getCode() ) ;
            $this->assertSame( 'The filter parameter must be a JSON object or list, ' . $type . ' given.' , $e->getMessage() ) ;
        }
        $this->assertSame( [] , $params ) ;
    }

    public function testAbsentFilterReturnsArgs(): void
    {
        $params = [] ;
        $this->assertSame( [ 'x' => 1 ] , $this->prepare( $this->host() , $this->request() , [ ControllerParam::FILTER => [ 'x' => 1 ] ] , $params ) ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testEmptyFilterMeansNoFilter(): void
    {
        $params = [] ;
        $this->assertSame( [ 'x' => 1 ] , $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => '' ] ) , [ ControllerParam::FILTER => [ 'x' => 1 ] ] , $params ) ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testNullFilterMeansNoFilter(): void
    {
        $params = [] ;
        $this->assertSame( [ 'x' => 1 ] , $this->prepare( $this->host() , $this->request( [ ControllerParam::FILTER => 'null' ] ) , [ ControllerParam::FILTER => [ 'x' => 1 ] ] , $params ) ) ;
        $this->assertSame( [] , $params ) ;
    }

    public function testNoRequestReturnsArgs(): void
    {
        $params = [] ;
        $this->assertSame( [ 'x' => 1 ] , $this->prepare( $this->host() , null , [ ControllerParam::FILTER => [ 'x' => 1 ] ] , $params ) ) ;
    }
}
