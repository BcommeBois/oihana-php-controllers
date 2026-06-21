<?php

namespace tests\oihana\controllers\traits\prepare;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareLang;

final class PrepareLangTest extends PrepareTestCase
{
    private function host(): object
    {
        $host = new class { use PrepareLang; } ;
        $host->languages = [ 'fr' , 'en' ] ;
        return $host ;
    }

    private function prepare( object $host , $request , array $args , array &$params ): ?string
    {
        $ref  = new \ReflectionMethod( $host , 'prepareLang' ) ;
        $a    = [ $request , $args , &$params ] ;
        return $ref->invokeArgs( $host , $a ) ;
    }

    public function testKnownLanguageIsLowercasedAndRegistered(): void
    {
        $params = [ 'seed' => true ] ; // non-empty so the `&& $params` guard registers
        // Match is case-sensitive against $languages, then lowercased.
        $this->assertSame( 'fr' , $this->prepare( $this->host() , $this->request( [ ControllerParam::LANG => 'fr' ] ) , [] , $params ) ) ;
        $this->assertSame( 'fr' , $params[ ControllerParam::LANG ] ) ;
    }

    public function testAllResetsLanguageToNull(): void
    {
        $params = [] ;
        $this->assertNull( $this->prepare( $this->host() , $this->request( [ ControllerParam::LANG => 'all' ] ) , [] , $params ) ) ;
    }

    public function testUnknownLanguageKeepsArgValue(): void
    {
        $params = [] ;
        $this->assertSame( 'es' , $this->prepare( $this->host() , $this->request( [ ControllerParam::LANG => 'de' ] ) , [ ControllerParam::LANG => 'es' ] , $params ) ) ;
    }
}
