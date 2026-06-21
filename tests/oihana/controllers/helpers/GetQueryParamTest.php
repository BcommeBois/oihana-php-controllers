<?php

namespace oihana\controllers\helpers ;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetQueryParamTest extends TestCase
{
    public function testReturnsNullWhenRequestIsNull(): void
    {
        $this->assertNull(getQueryParam(null, 'foo'));
    }

    public function testReturnsNullWhenQueryParamsAreEmpty(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $this->assertNull(getQueryParam($request, 'foo'));
    }

    public function testReturnsValueForSimpleKey(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn
        ([
            'name' => 'Alice',
            'age'  => '30',
        ]);

        $this->assertSame('Alice', getQueryParam($request, 'name'));
        $this->assertSame('30', getQueryParam($request, 'age'));
    }

    public function testReturnsValueForNestedKeyUsingDotNotation(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn
        ([
            'filter' => [
                'page'  => '2',
                'limit' => '10',
            ],
        ]);

        $this->assertSame('2', getQueryParam($request, 'filter.page'));
        $this->assertSame('10', getQueryParam($request, 'filter.limit'));
    }

    public function testReturnsNullForMissingKey(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn
        ([
            'foo' => 'bar',
        ]);

        $this->assertNull(getQueryParam($request, 'missing'));
        $this->assertNull(getQueryParam($request, 'foo.bar'));
    }

    public function testHandlesNestedArraysAndMixedTypes(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn
        ([
            'user' =>
            [
                'profile' =>
                [
                    'email'  => 'a@b.c',
                    'active' => '1',
                ],
                'roles' => ['admin', 'editor'],
            ],
        ]);

        $this->assertSame('a@b.c', getQueryParam($request, 'user.profile.email'));
        $this->assertSame('1', getQueryParam($request, 'user.profile.active'));
        $this->assertSame(['admin', 'editor'], getQueryParam($request, 'user.roles'));
        $this->assertNull(getQueryParam($request, 'user.profile.missing'));
    }
}