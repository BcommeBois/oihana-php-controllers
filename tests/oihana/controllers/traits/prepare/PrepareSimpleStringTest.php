<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareGroupBy;
use oihana\controllers\traits\prepare\PrepareSearch;
use oihana\controllers\traits\prepare\PrepareSort;

/**
 * Search / Sort / GroupBy: simple string parameter preparers.
 */
final class PrepareSimpleStringTest extends PrepareTestCase
{
    public function testPrepareSearchFromQuery(): void
    {
        $host = new class { use PrepareSearch; public function call( $r , array $a , ?array &$p ): ?string { return $this->prepareSearch( $r , $a , $p ); } } ;

        $params = [] ;
        $this->assertSame( 'abc' , $host->call( $this->request( [ ControllerParam::SEARCH => 'abc' ] ) , [] , $params ) ) ;
        $this->assertSame( 'abc' , $params[ ControllerParam::SEARCH ] ) ;
    }

    public function testPrepareSearchFromArgsWithoutRequest(): void
    {
        $host = new class { use PrepareSearch; public function call( $r , array $a , ?array &$p ): ?string { return $this->prepareSearch( $r , $a , $p ); } } ;
        $params = [] ;
        $this->assertSame( 'x' , $host->call( null , [ ControllerParam::SEARCH => 'x' ] , $params ) ) ;
    }

    public function testPrepareSortFromQueryElseDefault(): void
    {
        $host = new class { use PrepareSort; public function call( $r , array $a , ?array &$p , ?string $d = null ): ?string { return $this->prepareSort( $r , $a , $p , $d ); } } ;

        $params = [] ;
        $this->assertSame( 'name' , $host->call( $this->request( [ ControllerParam::SORT => 'name' ] ) , [] , $params ) ) ;
        $this->assertSame( 'name' , $params[ ControllerParam::SORT ] ) ;

        $this->assertSame( 'fallback' , $host->call( null , [] , $params , 'fallback' ) ) ;
    }

    public function testPrepareGroupByRegistersValue(): void
    {
        $host = new class { use PrepareGroupBy; } ;

        $params  = [ 'seed' => true ] ; // non-empty so the `&& $params` guard passes
        $groupBy = null ;

        $ref  = new \ReflectionMethod( $host , 'prepareGroupBy' ) ;
        $args = [ $this->request( [ ControllerParam::GROUP_BY => 'category' ] ) , &$params , &$groupBy ] ;
        $ref->invokeArgs( $host , $args ) ;

        $this->assertSame( 'category' , $groupBy ) ;
        $this->assertSame( 'category' , $params[ ControllerParam::GROUP_BY ] ) ;
    }
}
