<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ForceDocumentUrlTrait;

use PHPUnit\Framework\TestCase;

final class ForceDocumentUrlTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use ForceDocumentUrlTrait;

            public function callForceDocumentUrl( null|object|array &$document , ?string $url , string $propertyName = ControllerParam::URL ): object|array|null
            {
                return $this->forceDocumentUrl( $document , $url , $propertyName );
            }

            public function callForceDocumentsUrl( null|array &$documents , ?string $url , ?string $key = null , string $propertyName = ControllerParam::URL ): void
            {
                $this->forceDocumentsUrl( $documents , $url , $key , $propertyName );
            }
        };
    }

    // ----------------------------------------------------------- init

    public function testInitializeForceUrlFromInit(): void
    {
        $result = $this->mock->initializeForceUrl
        ([
            ControllerParam::DOCUMENT_KEY => '_id' ,
            ControllerParam::FORCE_URL    => true ,
        ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( '_id' , $this->mock->documentKey );
        $this->assertTrue( $this->mock->forceUrl );
    }

    public function testInitializeForceUrlKeepsDefaults(): void
    {
        $this->mock->initializeForceUrl();

        $this->assertSame( ControllerParam::ID , $this->mock->documentKey );
        $this->assertFalse( $this->mock->forceUrl );
    }

    // ----------------------------------------------------------- forceDocumentUrl (single)

    public function testForceDocumentUrlOnAssociativeArray(): void
    {
        $document = [ 'id' => 1 , 'name' => 'foo' ];
        $result   = $this->mock->callForceDocumentUrl( $document , '/api/foo' );

        $this->assertSame( '/api/foo' , $result[ ControllerParam::URL ] );
        $this->assertSame( '/api/foo' , $document[ ControllerParam::URL ] );
    }

    public function testForceDocumentUrlOnObject(): void
    {
        $document = (object) [ 'id' => 1 ];
        $this->mock->callForceDocumentUrl( $document , '/api/obj' );

        $this->assertSame( '/api/obj' , $document->{ ControllerParam::URL } );
    }

    public function testForceDocumentUrlOnNonAssociativeArrayDoesNothing(): void
    {
        $document = [ 'a' , 'b' ];
        $result   = $this->mock->callForceDocumentUrl( $document , '/api' );

        $this->assertSame( [ 'a' , 'b' ] , $result );
    }

    public function testForceDocumentUrlWithNullDocumentReturnsNull(): void
    {
        $document = null;
        $this->assertNull( $this->mock->callForceDocumentUrl( $document , '/api' ) );
    }

    // ----------------------------------------------------------- forceDocumentsUrl (list)

    public function testForceDocumentsUrlOnArrayItems(): void
    {
        $documents =
        [
            [ 'id' => 1 ] ,
            [ 'id' => 2 ] ,
        ];

        $this->mock->callForceDocumentsUrl( $documents , '/api' , 'id' );

        $this->assertSame( '/api/1' , $documents[0][ ControllerParam::URL ] );
        $this->assertSame( '/api/2' , $documents[1][ ControllerParam::URL ] );
    }

    public function testForceDocumentsUrlOnObjectItemsUsingDefaultKey(): void
    {
        $this->mock->initializeForceUrl([ ControllerParam::DOCUMENT_KEY => 'id' ]);

        $documents = [ (object) [ 'id' => 7 ] ];
        $this->mock->callForceDocumentsUrl( $documents , '/api' );

        $this->assertSame( '/api/7' , $documents[0]->{ ControllerParam::URL } );
    }

    public function testForceDocumentsUrlSkipsItemsWithoutKey(): void
    {
        $documents = [ [ 'name' => 'no-id' ] ];
        $this->mock->callForceDocumentsUrl( $documents , '/api' , 'id' );

        $this->assertArrayNotHasKey( ControllerParam::URL , $documents[0] );
    }

    public function testForceDocumentsUrlOnEmptyArrayDoesNothing(): void
    {
        $documents = [];
        $this->mock->callForceDocumentsUrl( $documents , '/api' , 'id' );

        $this->assertSame( [] , $documents );
    }
}
