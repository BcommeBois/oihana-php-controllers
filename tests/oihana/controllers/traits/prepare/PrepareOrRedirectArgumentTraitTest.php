<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\traits\prepare\PrepareOrRedirectArgumentTrait;

final class PrepareOrRedirectArgumentTraitTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class { use PrepareOrRedirectArgumentTrait; } ;
    }

    public function testRedirectsWhenMappingExists(): void
    {
        $host = $this->host() ;
        $host->redirects = [ 'group' => [ 'old' => 'new' ] ] ;

        $this->assertSame( 'new' , $host->prepareOrRedirectArgument( 'old' , 'group' ) ) ;
    }

    public function testReturnsIdWhenNoMapping(): void
    {
        $host = $this->host() ;
        $host->redirects = [ 'group' => [ 'old' => 'new' ] ] ;

        $this->assertSame( 'other' , $host->prepareOrRedirectArgument( 'other' , 'group' ) ) ;
    }

    public function testReturnsIdWhenRedirectIdNull(): void
    {
        $this->assertSame( 'x' , $this->host()->prepareOrRedirectArgument( 'x' , null ) ) ;
    }

    public function testReturnsNullWhenIdNull(): void
    {
        $this->assertNull( $this->host()->prepareOrRedirectArgument( null , 'group' ) ) ;
    }
}
