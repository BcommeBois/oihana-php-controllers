<?php

namespace tests\oihana\controllers\traits\prepare;

use xyz\oihana\schema\Pagination;

use oihana\controllers\traits\prepare\PrepareLimit;

final class PrepareLimitTest extends PrepareTestCase
{
    private function host(): object
    {
        // LimitTrait already declares ?int $limit / ?int $offset; set them on the instance.
        $host = new class { use PrepareLimit; } ;
        $host->limit  = 20 ;
        $host->offset = 0 ;
        return $host ;
    }

    private function prepareLimit( object $host , $request , array $args , array &$params , int $default = 0 ): int
    {
        $ref = new \ReflectionMethod( $host , 'prepareLimit' ) ;
        $a   = [ $request , $args , &$params , $default ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    public function testQueryLimitIsClampedAndRegistered(): void
    {
        $host = $this->host() ;
        $host->minLimit = 0 ;
        $host->maxLimit = 100 ;

        $params = [] ;
        $this->assertSame( 50 , $this->prepareLimit( $host , $this->request( [ Pagination::LIMIT => '50' ] ) , [] , $params ) ) ;
        $this->assertSame( 50 , $params[ Pagination::LIMIT ] ) ;
    }

    public function testQueryLimitAboveMaxIsRejectedAndFallsBackToProperty(): void
    {
        $host = $this->host() ;
        $host->minLimit = 0 ;
        $host->maxLimit = 100 ;

        $params = [] ;
        // 999 is out of [0,100] -> filter_var returns false -> not int -> property value.
        $this->assertSame( 20 , $this->prepareLimit( $host , $this->request( [ Pagination::LIMIT => '999' ] ) , [] , $params ) ) ;
    }

    public function testWithoutRequestUsesPropertyValue(): void
    {
        $params = [] ;
        $this->assertSame( 20 , $this->prepareLimit( $this->host() , null , [] , $params ) ) ;
    }

    public function testPrepareOffsetDelegates(): void
    {
        $host = $this->host() ;
        $params = [] ;

        $ref = new \ReflectionMethod( $host , 'prepareOffset' ) ;
        $a   = [ $this->request( [ Pagination::OFFSET => '15' ] ) , [] , &$params , 0 ] ;

        $this->assertSame( 15 , $ref->invokeArgs( $host , $a ) ) ;
        $this->assertSame( 15 , $params[ Pagination::OFFSET ] ) ;
    }
}
