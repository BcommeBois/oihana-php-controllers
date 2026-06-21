<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamIntTest extends TestCase
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
    public function testReturnsIntFromQuery()
    {
        $request = $this->createRequest(['age' => '42']);
        $this->assertSame(42, getParamInt($request, 'age'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsIntFromBody()
    {
        $request = $this->createRequest([], ['age' => '19']);
        $this->assertSame(19, getParamInt($request, 'age', [], null, HttpParamStrategy::BODY));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultForMissingKey()
    {
        $request = $this->createRequest([]);
        $this->assertSame(10, getParamInt($request, 'missing', [], 10));
        $this->assertNull(getParamInt($request, 'missing'));
    }

    public function testThrowsNotFoundExceptionWhenThrowable()
    {
        $this->expectException(NotFoundException::class);
        $request = $this->createRequest([]);
        getParamInt($request, 'missing', [], null, HttpParamStrategy::BOTH, true);
    }

    /**
     * @throws NotFoundException
     */
    public function testSearchesQueryWhenStrategyQuery()
    {
        $request = $this->createRequest(['count' => 5], ['count' => 10]);
        $this->assertSame(5, getParamInt($request, 'count', [], null, HttpParamStrategy::QUERY));
    }

    /**
     * @throws NotFoundException
     */
    public function testSearchesBodyWhenStrategyBody()
    {
        $request = $this->createRequest(['count' => 5], ['count' => 10]);
        $this->assertSame(10, getParamInt($request, 'count', [], null, HttpParamStrategy::BODY));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultIfValueIsNotNumeric()
    {
        $request = $this->createRequest(['count' => 'abc']);
        $this->assertSame(99, getParamInt($request, 'count', [], 99));
        $this->assertNull(getParamInt($request, 'count'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsNullWhenRequestIsNull()
    {
        $this->assertNull(getParamInt(null, 'any'));
        $this->assertSame(42, getParamInt(null, 'any', [], 42));
    }

    /**
     * @throws NotFoundException
     */
    public function testHandlesNumericStringsAndZero()
    {
        $request = $this->createRequest(['zero' => '0', 'float' => '3.7']);
        $this->assertSame(0, getParamInt($request, 'zero'));
        $this->assertSame(3, getParamInt($request, 'float'));
    }
}