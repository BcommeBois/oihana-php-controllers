<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareSkin;

final class PrepareSkinTest extends PrepareTestCase
{
    private function host(): object
    {
        return new class { use PrepareSkin; } ;
    }

    private function prepare( object $host , $request , array $init , array &$params , ?string $method = null ): ?string
    {
        $ref = new \ReflectionMethod( $host , 'prepareSkin' ) ;
        $a   = [ $request , $init , &$params , $method ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    public function testInitializeSkins(): void
    {
        $host = $this->host() ;
        ( new \ReflectionMethod( $host , 'initializeSkins' ) )->invoke( $host ,
        [
            ControllerParam::SKIN_DEFAULT => 'compact' ,
            ControllerParam::SKIN_METHODS => [ 'edit' => 'editor' ] ,
            ControllerParam::SKINS        => [ 'compact' , 'editor' ] ,
        ]) ;

        $this->assertSame( 'compact' , $host->skinDefault ) ;
        $this->assertSame( [ 'edit' => 'editor' ] , $host->skinMethods ) ;
        $this->assertSame( [ 'compact' , 'editor' ] , $host->skins ) ;
    }

    public function testValidQuerySkinIsRegistered(): void
    {
        $host = $this->host() ;
        $host->skins = [ 'compact' , 'editor' ] ;

        $params = [] ;
        $result = $this->prepare( $host , $this->request( [ ControllerParam::SKIN => 'editor' ] ) , [] , $params ) ;

        $this->assertSame( 'editor' , $result ) ;
        $this->assertSame( 'editor' , $params[ ControllerParam::SKIN ] ) ;
    }

    public function testMethodSpecificSkinOverride(): void
    {
        $host = $this->host() ;
        $host->skins       = [ 'compact' , 'editor' ] ;
        $host->skinMethods = [ 'edit' => 'editor' ] ;

        $params = [] ;
        $result = $this->prepare( $host , null , [] , $params , 'edit' ) ;

        $this->assertSame( 'editor' , $result ) ;
    }

    public function testInvalidSkinReturnsNull(): void
    {
        $host = $this->host() ;
        $host->skins = [ 'compact' ] ;

        $params = [] ;
        $this->assertNull( $this->prepare( $host , $this->request( [ ControllerParam::SKIN => 'unknown' ] ) , [] , $params ) ) ;
    }
}
