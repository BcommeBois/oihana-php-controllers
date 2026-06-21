<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\PathTrait;
use oihana\enums\Char;

use PHPUnit\Framework\TestCase;

final class PathTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use PathTrait;
        };
    }

    public function testInitializePathFromInit(): void
    {
        $result = $this->mock->initializePath
        ([
            ControllerParam::PATH       => 'users' ,
            ControllerParam::FULL_PATH  => '/api/users' ,
            ControllerParam::OWNER_PATH => 'owners' ,
        ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( 'users'      , $this->mock->path );
        $this->assertSame( '/api/users' , $this->mock->fullPath );
        $this->assertSame( 'owners'     , $this->mock->ownerPath );
    }

    public function testInitializePathDefaults(): void
    {
        $this->mock->initializePath();

        $this->assertSame( Char::EMPTY        , $this->mock->path );
        $this->assertSame( Char::SLASH        , $this->mock->fullPath ); // SLASH . '' (empty path)
        $this->assertSame( Char::EMPTY        , $this->mock->ownerPath );
    }

    public function testInitializePathDerivesFullPathFromPath(): void
    {
        $this->mock->initializePath([ ControllerParam::PATH => 'articles' ]);

        $this->assertSame( 'articles'  , $this->mock->path );
        $this->assertSame( '/articles' , $this->mock->fullPath );
    }

    public function testGetFullOwnerPath(): void
    {
        $this->mock->initializePath
        ([
            ControllerParam::PATH       => 'posts' ,
            ControllerParam::OWNER_PATH => 'users' ,
        ]);

        $this->assertSame( 'users/42/posts' , $this->mock->getFullOwnerPath( '42' ) );
    }
}
