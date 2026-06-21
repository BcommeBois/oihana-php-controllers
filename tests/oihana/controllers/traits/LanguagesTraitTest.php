<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\LanguagesTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

final class LanguagesTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use LanguagesTrait;
        };
    }

    public function testInitializeLanguagesFromInit(): void
    {
        $result = $this->mock->initializeLanguages([ ControllerParam::LANGUAGES => [ 'fr' , 'en' ] ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( [ 'fr' , 'en' ] , $this->mock->languages );
    }

    public function testInitializeLanguagesFromContainer(): void
    {
        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( [ 'de' , 'it' ] );

        $this->mock->initializeLanguages( [] , $container );

        $this->assertSame( [ 'de' , 'it' ] , $this->mock->languages );
    }

    public function testInitializeLanguagesNonArrayFallsBackToEmpty(): void
    {
        $this->mock->initializeLanguages([ ControllerParam::LANGUAGES => 'fr' ]);
        $this->assertSame( [] , $this->mock->languages );
    }

    public function testInitializeLanguagesEmptyByDefault(): void
    {
        $this->mock->initializeLanguages();
        $this->assertSame( [] , $this->mock->languages );
    }
}
