<?php

declare(strict_types=1);

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamTest extends TestCase
{
    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultWhenRequestIsNull(): void
    {
        $this->assertSame('default', getParam(null, 'foo', ['foo' => 'default']));
        $this->assertNull(getParam(null, 'bar'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsValueFromQuery(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'name' => 'Alice',
            'age'  => '30',
        ]);

        $this->assertSame('Alice', getParam($request, 'name', [], HttpParamStrategy::QUERY));
        $this->assertSame('30', getParam($request, 'age', [], HttpParamStrategy::QUERY));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsValueFromBody(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn
        ([
            'user' => [
                'email'  => 'a@b.c',
                'active' => true,
            ],
        ]);

        $this->assertSame('a@b.c', getParam($request, 'user.email', [], HttpParamStrategy::BODY));
        $this->assertTrue(getParam($request, 'user.active', [], HttpParamStrategy::BODY));
    }

    public function testReturnsValueFromBothQueryAndBody(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['name' => 'QueryName']);
        $request->method('getParsedBody')->willReturn(['name' => 'BodyName']);

        // With BOTH, query takes precedence
        $this->assertSame('QueryName', getParam($request, 'name', [], HttpParamStrategy::BOTH));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultForMissingKey(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn([]);

        $this->assertSame('default', getParam($request, 'foo', ['foo' => 'default'], HttpParamStrategy::BOTH));
        $this->assertNull(getParam($request, 'bar', [], HttpParamStrategy::BOTH));
    }

    public function testThrowsExceptionWhenThrowable(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The parameter "missing" was not found.');

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn([]);

        getParam($request, 'missing', [], HttpParamStrategy::BOTH, true);
    }

    /**
     * @throws NotFoundException
     */
    public function testHandlesNestedArraysAndDotNotation(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'geo' => [
                'latitude'  => 42.123,
                'longitude' => 1.456,
                'meta'      => ['alt' => null],
            ],
        ]);

        $this->assertSame(42.123, getParam($request, 'geo.latitude', [], HttpParamStrategy::BODY));
        $this->assertSame(1.456, getParam($request, 'geo.longitude', [], HttpParamStrategy::BODY));
        $this->assertNull(getParam($request, 'geo.meta.alt', [], HttpParamStrategy::BODY));
    }
}