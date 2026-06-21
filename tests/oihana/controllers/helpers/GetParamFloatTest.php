<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamFloatTest extends TestCase
{
    private function createRequest(array $query = [], array $body = []): ServerRequestInterface
    {
        $mock = $this->createStub(ServerRequestInterface::class);
        $mock->method('getQueryParams')->willReturn($query);
        $mock->method('getParsedBody')->willReturn($body);
        return $mock;
    }

    public function testReturnsFloatFromQuery()
    {
        $request = $this->createRequest(['price' => '19.95']);
        $this->assertSame(19.95, getParamFloat($request, 'price'));
    }

    public function testReturnsFloatFromBody()
    {
        $request = $this->createRequest([], ['discount' => '5.5']);
        $this->assertSame(5.5, getParamFloat($request, 'discount', [], null, HttpParamStrategy::BODY));
    }

    public function testCastsIntegerToFloat()
    {
        $request = $this->createRequest(['count' => 42]);
        $this->assertSame(42.0, getParamFloat($request, 'count'));
    }

    public function testReturnsDefaultForMissingKey()
    {
        $request = $this->createRequest([]);
        $this->assertSame(10.0, getParamFloat($request, 'missing', [], 10.0));
        $this->assertNull(getParamFloat($request, 'missing'));
    }

    public function testThrowsNotFoundExceptionWhenThrowable()
    {
        $this->expectException(NotFoundException::class);
        $request = $this->createRequest([]);
        getParamFloat($request, 'missing', [], null, HttpParamStrategy::BOTH, true);
    }

    public function testSearchesQueryWhenStrategyQuery()
    {
        $request = $this->createRequest(['value' => 3.14], ['value' => 2.71]);
        $this->assertSame(3.14, getParamFloat($request, 'value', [], null, HttpParamStrategy::QUERY));
    }

    public function testSearchesBodyWhenStrategyBody()
    {
        $request = $this->createRequest(['value' => 3.14], ['value' => 2.71]);
        $this->assertSame(2.71, getParamFloat($request, 'value', [], null, HttpParamStrategy::BODY));
    }

    public function testReturnsDefaultIfValueIsNotNumeric()
    {
        $request = $this->createRequest(['value' => 'abc']);
        $this->assertSame(1.23, getParamFloat($request, 'value', [], 1.23));
        $this->assertNull(getParamFloat($request, 'value'));
    }

    public function testReturnsNullWhenRequestIsNull()
    {
        $this->assertNull(getParamFloat(null, 'any'));
        $this->assertSame(0.0, getParamFloat(null, 'any', [], 0.0));
    }
}