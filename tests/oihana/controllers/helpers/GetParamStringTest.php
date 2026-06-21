<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamStringTest extends TestCase
{
    private function createRequest(array $query = [], array $body = []): ServerRequestInterface
    {
        $mock = $this->createStub(ServerRequestInterface::class);
        $mock->method('getQueryParams')->willReturn($query);
        $mock->method('getParsedBody')->willReturn($body);
        return $mock;
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsStringFromQuery()
    {
        $request = $this->createRequest(['name' => 'Alice']);
        $this->assertSame('Alice', getParamString($request, 'name'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsStringFromBody()
    {
        $request = $this->createRequest([], ['title' => 'Manager']);
        $this->assertSame('Manager', getParamString($request, 'title', [], null, HttpParamStrategy::BODY));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultForMissingKey()
    {
        $request = $this->createRequest([]);
        $this->assertSame('Guest', getParamString($request, 'nickname', [], 'Guest'));
        $this->assertNull(getParamString($request, 'nickname'));
    }

    public function testThrowsNotFoundExceptionWhenThrowable()
    {
        $this->expectException(NotFoundException::class);
        $request = $this->createRequest([]);
        getParamString($request, 'missing', [], null, HttpParamStrategy::BOTH, true);
    }

    /**
     * @throws NotFoundException
     */
    public function testSearchesQueryWhenStrategyQuery()
    {
        $request = $this->createRequest(['field' => 'value1'], ['field' => 'value2']);
        $this->assertSame('value1', getParamString($request, 'field', [], null, HttpParamStrategy::QUERY));
    }

    /**
     * @throws NotFoundException
     */
    public function testSearchesBodyWhenStrategyBody()
    {
        $request = $this->createRequest(['field' => 'value1'], ['field' => 'value2']);
        $this->assertSame('value2', getParamString($request, 'field', [], null, HttpParamStrategy::BODY));
    }

    /**
     * @throws NotFoundException
     */
    public function testCastsValueToString()
    {
        $request = $this->createRequest(['count' => 42]);
        $this->assertSame('42', getParamString($request, 'count'));

        $request = $this->createRequest(['enabled' => true]);
        $this->assertSame('1', getParamString($request, 'enabled'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsNullWhenRequestIsNull()
    {
        $this->assertNull(getParamString(null, 'any'));
        $this->assertSame('default', getParamString(null, 'any', [], 'default'));
    }
}