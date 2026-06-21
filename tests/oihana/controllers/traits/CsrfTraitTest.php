<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\CsrfTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;

final class CsrfTraitTest extends TestCase
{
    private object $mock;

    /** @var array<string,mixed> CSRF storage backing the real Guard. */
    private array $storage = [];

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use CsrfTrait;
        };
    }

    private function guard(): Guard
    {
        return new Guard( new ResponseFactory() , 'csrf' , $this->storage ) ;
    }

    public function testAccessorsDegradeWhenNotConfigured(): void
    {
        $this->assertNull( $this->mock->csrfTokenName() );
        $this->assertNull( $this->mock->csrfTokenNameKey() );
        $this->assertNull( $this->mock->csrfTokenValue() );
        $this->assertNull( $this->mock->csrfTokenValueKey() );
        $this->assertSame( [] , $this->mock->csrfTokens() );
        $this->assertSame( [] , $this->mock->generateCsrfToken() );
        $this->assertFalse( $this->mock->validateCsrf( 'name' , 'value' ) );
    }

    public function testInitializeFromInit(): void
    {
        $guard = $this->guard() ;
        $this->mock->initializeCsrf( [ $this->mock::CSRF => $guard ] ) ;

        $this->assertSame( 'csrf_name'  , $this->mock->csrfTokenNameKey() );
        $this->assertSame( 'csrf_value' , $this->mock->csrfTokenValueKey() );
    }

    public function testInitializeFromContainer(): void
    {
        $guard     = $this->guard() ;
        $container = $this->createStub( ContainerInterface::class ) ;
        $container->method('has')->willReturn( true ) ;
        $container->method('get')->willReturn( $guard ) ;

        $this->mock->initializeCsrf( [] , $container ) ;

        $this->assertSame( 'csrf_name' , $this->mock->csrfTokenNameKey() );
    }

    public function testInitializeContainerWithoutGuardStaysNull(): void
    {
        $container = $this->createStub( ContainerInterface::class ) ;
        $container->method('has')->willReturn( false ) ;

        $this->mock->initializeCsrf( [] , $container ) ;

        $this->assertNull( $this->mock->csrfTokenNameKey() );
    }

    public function testTokensEmptyBeforeGeneration(): void
    {
        $this->mock->initializeCsrf( [ $this->mock::CSRF => $this->guard() ] ) ;

        $this->assertNull( $this->mock->csrfTokenName() );
        $this->assertNull( $this->mock->csrfTokenValue() );
        $this->assertSame( [] , $this->mock->csrfTokens() );
    }

    public function testGenerateCsrfTokenPopulatesAccessors(): void
    {
        $this->mock->initializeCsrf( [ $this->mock::CSRF => $this->guard() ] ) ;

        $pair = $this->mock->generateCsrfToken() ;

        $name  = $this->mock->csrfTokenName() ;
        $value = $this->mock->csrfTokenValue() ;

        $this->assertNotNull( $name );
        $this->assertNotNull( $value );
        $this->assertSame( [ 'csrf_name' => $name , 'csrf_value' => $value ] , $pair );
        $this->assertSame( $pair , $this->mock->csrfTokens() );
    }

    public function testValidateCsrfTrueForGeneratedToken(): void
    {
        $this->mock->initializeCsrf( [ $this->mock::CSRF => $this->guard() ] ) ;
        $this->mock->generateCsrfToken() ;

        $this->assertTrue( $this->mock->validateCsrf( $this->mock->csrfTokenName() , $this->mock->csrfTokenValue() ) );
    }

    public function testValidateCsrfFalseForWrongValue(): void
    {
        $this->mock->initializeCsrf( [ $this->mock::CSRF => $this->guard() ] ) ;
        $this->mock->generateCsrfToken() ;

        $this->assertFalse( $this->mock->validateCsrf( $this->mock->csrfTokenName() , 'tampered' ) );
    }
}
