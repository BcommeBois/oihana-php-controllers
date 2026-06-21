<?php

namespace tests\oihana\controllers\traits;

use PHPUnit\Framework\TestCase;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\BaseUrlTrait;
use oihana\enums\Char;
use oihana\enums\ServerParam;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Http\Message\UriInterface;

final class BaseUrlTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use BaseUrlTrait;
        };
    }

    public function testInitializeBaseUrlFromInit()
    {
        $result = $this->mock->initializeBaseUrl
        ([
            ControllerParam::BASE_URL => '/api/v1',
        ]);

        $this->assertSame($this->mock, $result);
        $this->assertSame('/api/v1', $this->mock->baseUrl);
    }

    public function testInitializeBaseUrlFromContainer()
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn('/from-container');

        $this->mock->initializeBaseUrl([], $container);

        $this->assertSame('/from-container', $this->mock->baseUrl);
    }

    public function testInitializeBaseUrlWithEmptyFallback()
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $this->mock->initializeBaseUrl([], $container);

        $this->assertSame(Char::EMPTY, $this->mock->baseUrl);
    }

    public function testGetCurrentPathFromRequest()
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/users/123');

        $request = $this->createStub(RequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $this->mock->baseUrl = '/api';

        $result = $this->mock->getCurrentPath($request);

        $this->assertSame('/api/users/123', $result);
    }

    public function testGetCurrentPathFromServerGlobal()
    {
        $_SERVER[ServerParam::REQUEST_URI] = '/foo/bar';
        $this->mock->baseUrl = '/base';

        $result = $this->mock->getCurrentPath();

        $this->assertSame('/base/foo/bar', $result);
    }

    public function testGetCurrentPathWithParamsAndUseNow()
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/products');

        $request = $this->createStub(RequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $this->mock->baseUrl = '/shop';

        $result = $this->mock->getCurrentPath($request, ['q' => 'abc'], true);

        // Le useNow ne change rien si 'from' n'existe pas
        $this->assertSame('/shop/products?q=abc', $result);
    }

    public function testGetFullPathWithoutParams()
    {
        $this->mock->baseUrl = '/api';
        $result = $this->mock->getFullPath();

        $this->assertSame('/api', $result);
    }

    public function testGetFullPathWithParams()
    {
        $this->mock->baseUrl = '/api';
        $result = $this->mock->getFullPath(['page' => 2, 'size' => 10]);

        $this->assertSame('/api?page=2&size=10', $result);
    }

    public function testGetFullPathWithUseNow()
    {
        $this->mock->baseUrl = '/api';
        $result = $this->mock->getFullPath(['test' => 1], true);
        $this->assertSame('/api?test=1', $result);
    }

    public function testGetPathCombinesBaseAndRelativePath()
    {
        $this->mock->baseUrl = '/api';
        $result = $this->mock->getPath('/users');

        $this->assertSame('/api/users', $result);
    }

    public function testGetPathWithParamsAndUseNow()
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getPath')->willReturn('/events');

        $request = $this->createStub(RequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        $this->mock->baseUrl = '/app';

        $result = $this->mock->getCurrentPath($request, ['from' => 'yesterday'], true);

        $this->assertSame('/app/events?from=now', $result);
    }
}