<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamArrayTest extends TestCase
{
    private function createRequest(array $query = [], $body = null): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($query);
        $request->method('getParsedBody')->willReturn($body);
        return $request;
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsNullWhenRequestIsNull()
    {
        $this->assertNull( getParamArray(null, 'foo'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultWhenValueIsNotArray()
    {
        $request = $this->createRequest(['foo' => 'bar']);
        $default = ['default'];
        $this->assertSame($default, getParamArray($request, 'foo', [], $default));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsArrayFromQuery()
    {
        $request = $this->createRequest(['filters' => ['status' => 'active', 'roles' => ['admin']]]);
        $expected = ['status' => 'active', 'roles' => ['admin']];
        $this->assertSame($expected, getParamArray($request, 'filters', [], []));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsArrayFromBody()
    {
        $body = ['user' => ['roles' => ['editor', 'admin']]];
        $request = $this->createRequest([], $body);
        $expected = ['editor', 'admin'];
        $this->assertSame($expected, getParamArray($request, 'user.roles', [], []));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsArrayFromBothStrategyQueryFirst()
    {
        $request = $this->createRequest(
            ['filters' => ['status' => 'query']],
            ['filters' => ['status' => 'body']]
        );
        $expected = ['status' => 'query'];
        $this->assertSame($expected, getParamArray($request, 'filters', [], [], HttpParamStrategy::BOTH));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsArrayFromBothStrategyBodyIfQueryMissing()
    {
        $body = ['filters' => ['status' => 'body']];
        $request = $this->createRequest([], $body);
        $expected = ['status' => 'body'];
        $this->assertSame($expected, getParamArray($request, 'filters', [], [], HttpParamStrategy::BOTH));
    }

    /**
     * @throws NotFoundException
     */
    public function testHandlesNestedArraysWithDotNotation()
    {
        $body = ['geo' => ['latitude' => 42.123, 'longitude' => 1.456]];
        $request = $this->createRequest([], $body);
        $expected = ['latitude' => 42.123, 'longitude' => 1.456];
        $this->assertSame($expected, getParamArray($request, 'geo', [], []));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultForMissingKey()
    {
        $request = $this->createRequest(['foo' => ['bar' => 1]]);
        $default = ['baz' => 2];
        $this->assertSame($default, getParamArray($request, 'missing', [], $default));
    }

    public function testThrowsNotFoundExceptionWhenThrowable()
    {
        $this->expectException(NotFoundException::class);
        $request = $this->createRequest(['foo' => 'bar']);
        getParamArray($request, 'missing', [], null, HttpParamStrategy::BOTH, true);
    }

    /**
     * @throws NotFoundException
     */
    public function testNestedObjectsConvertedToArray()
    {
        $body = ['user' => (object)['profile' => (object)['email' => 'a@b.c']]];
        $request = $this->createRequest([], $body);
        $expected = ['email' => 'a@b.c'];
        $this->assertSame($expected, getParamArray($request, 'user.profile', [], []));
    }
}