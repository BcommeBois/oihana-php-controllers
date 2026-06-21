<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\HttpCacheTrait;

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use Slim\HttpCache\CacheProvider;

final class HttpCacheTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use HttpCacheTrait;
        };
    }

    /**
     * A CacheProvider stub whose every method echoes back a sentinel response,
     * so we can assert the trait delegated to it.
     */
    private function cacheProvider( ResponseInterface $returned ): CacheProvider
    {
        $provider = $this->createStub( CacheProvider::class );
        $provider->method('allowCache')->willReturn( $returned );
        $provider->method('denyCache')->willReturn( $returned );
        $provider->method('withEtag')->willReturn( $returned );
        $provider->method('withExpires')->willReturn( $returned );
        $provider->method('withLastModified')->willReturn( $returned );
        return $provider;
    }

    // ------------------------------------------------------------------ init

    public function testInitializeHttpCacheFromInit(): void
    {
        $provider = $this->createStub( CacheProvider::class );

        $result = $this->mock->initializeHttpCache([ ControllerParam::HTTP_CACHE => $provider ]);

        $this->assertSame( $this->mock , $result );
        // a subsequent delegating call proves the provider was stored
        $sentinel = $this->createStub( ResponseInterface::class );
        $providerEcho = $this->cacheProvider( $sentinel );
        $this->mock->initializeHttpCache([ ControllerParam::HTTP_CACHE => $providerEcho ]);
        $this->assertSame( $sentinel , $this->mock->denyCache( $this->createStub( ResponseInterface::class ) ) );
    }

    public function testInitializeHttpCacheFromContainer(): void
    {
        $sentinel     = $this->createStub( ResponseInterface::class );
        $provider     = $this->cacheProvider( $sentinel );

        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( true );
        $container->method('get')->willReturn( $provider );

        $result = $this->mock->initializeHttpCache( [] , $container );

        $this->assertSame( $this->mock , $result );
        $this->assertSame( $sentinel , $this->mock->allowCache( $this->createStub( ResponseInterface::class ) ) );
    }

    public function testInitializeHttpCacheWithoutProviderLeavesItNull(): void
    {
        $container = $this->createStub( ContainerInterface::class );
        $container->method('has')->willReturn( false );

        $this->mock->initializeHttpCache( [] , $container );

        // no provider: every helper returns the response untouched
        $response = $this->createStub( ResponseInterface::class );
        $this->assertSame( $response , $this->mock->allowCache( $response ) );
    }

    // ------------------------------------------------------- pass-through (null provider)

    public function testHelpersReturnResponseUnchangedWhenNoProvider(): void
    {
        $response = $this->createStub( ResponseInterface::class );

        $this->assertSame( $response , $this->mock->allowCache( $response ) );
        $this->assertSame( $response , $this->mock->denyCache( $response ) );
        $this->assertSame( $response , $this->mock->withEtag( $response , 'abc' ) );
        $this->assertSame( $response , $this->mock->withExpires( $response , 3600 ) );
        $this->assertSame( $response , $this->mock->withLastModified( $response , 3600 ) );
    }

    // ------------------------------------------------------- delegation (provider set)

    public function testHelpersDelegateToProviderWhenSet(): void
    {
        $sentinel = $this->createStub( ResponseInterface::class );
        $provider = $this->cacheProvider( $sentinel );

        $this->mock->initializeHttpCache([ ControllerParam::HTTP_CACHE => $provider ]);

        $input = $this->createStub( ResponseInterface::class );

        $this->assertSame( $sentinel , $this->mock->allowCache( $input , 'public' , 600 , true ) );
        $this->assertSame( $sentinel , $this->mock->denyCache( $input ) );
        $this->assertSame( $sentinel , $this->mock->withEtag( $input , 'etag-value' , 'weak' ) );
        $this->assertSame( $sentinel , $this->mock->withExpires( $input , '+1 hour' ) );
        $this->assertSame( $sentinel , $this->mock->withLastModified( $input , 1700000000 ) );
    }
}
