<?php

namespace tests\oihana\controllers\traits;

use InvalidArgumentException;

use oihana\controllers\traits\TwigTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Views\Twig;

final class TwigTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use TwigTrait;
        };
    }

    // ----------------------------------------------------------- init

    public function testInitializeTwigFromInit(): void
    {
        $twig   = $this->createStub( Twig::class );
        $result = $this->mock->initializeTwig([ $this->mock::TWIG => $twig ]);

        $this->assertSame( $this->mock , $result );
        $this->assertSame( $twig , $this->mock->twig );
    }

    public function testInitializeTwigFromContainerByTwigKey(): void
    {
        $twig = $this->createStub( Twig::class );

        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturnCallback( fn( $key ) => $key === $this->mock::TWIG );
        $container->method('get')->willReturn( $twig );

        $this->mock->initializeTwig( [] , $container );

        $this->assertSame( $twig , $this->mock->twig );
    }

    public function testInitializeTwigFromContainerByClassName(): void
    {
        $twig = $this->createStub( Twig::class );

        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturnCallback( fn( $key ) => $key === Twig::class );
        $container->method('get')->willReturn( $twig );

        $this->mock->initializeTwig( [] , $container );

        $this->assertSame( $twig , $this->mock->twig );
    }

    public function testInitializeTwigThrowsWhenNoInstanceAvailable(): void
    {
        $this->expectException( InvalidArgumentException::class );
        $this->mock->initializeTwig();
    }

    // ----------------------------------------------------------- render

    public function testRenderReturnsNullWhenNoResponse(): void
    {
        $this->mock->initializeTwig([ $this->mock::TWIG => $this->createStub( Twig::class ) ]);

        $this->assertNull( $this->mock->render( null , 'home.twig' ) );
    }

    public function testRenderAddsHtmlContentTypeWhenMissing(): void
    {
        $rendered = $this->createStub( Response::class );
        $rendered->method('hasHeader')->willReturn( false );
        $rendered->method('withHeader')->willReturnSelf();

        $twig = $this->createStub( Twig::class );
        $twig->method('render')->willReturn( $rendered );

        $this->mock->initializeTwig([ $this->mock::TWIG => $twig ]);

        $response = $this->createStub( Response::class );
        $result   = $this->mock->render( $response , 'home.twig' , [ 'title' => 'Hi' ] );

        $this->assertSame( $rendered , $result );
    }

    public function testRenderKeepsExistingContentType(): void
    {
        $rendered = $this->createStub( Response::class );
        $rendered->method('hasHeader')->willReturn( true );

        $twig = $this->createStub( Twig::class );
        $twig->method('render')->willReturn( $rendered );

        $this->mock->initializeTwig([ $this->mock::TWIG => $twig ]);

        $result = $this->mock->render( $this->createStub( Response::class ) , 'page.svg' );

        $this->assertSame( $rendered , $result );
    }
}
