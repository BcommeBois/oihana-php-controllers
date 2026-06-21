<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamNumberRangeTest extends TestCase
{
    private function createRequest(array $query = [], array $body = []): ServerRequestInterface
    {
        $mock = $this->createStub(ServerRequestInterface::class);
        $mock->method('getQueryParams')->willReturn($query);
        $mock->method('getParsedBody')->willReturn($body);
        return $mock;
    }

    /** @throws NotFoundException */
    public function testReturnsValueWithinRange()
    {
        $request = $this->createRequest(['price' => 50]);
        $this->assertSame(50, getParamNumberRange($request, 'price', 10, 100));
    }

    /** @throws NotFoundException */
    public function testClampsValueBelowMin()
    {
        $request = $this->createRequest(['price' => 5]);
        $this->assertSame(10, getParamNumberRange($request, 'price', 10, 100));
    }

    /** @throws NotFoundException */
    public function testClampsValueAboveMax()
    {
        $request = $this->createRequest(['price' => 150]);
        $this->assertSame(100, getParamNumberRange($request, 'price', 10, 100));
    }

    /** @throws NotFoundException */
    public function testReturnsDefaultForMissingOrInvalidValue()
    {
        $request = $this->createRequest([]);
        $this->assertSame(42, getParamNumberRange($request, 'price', 10, 100, 42));

        $request = $this->createRequest(['price' => 'abc']);
        $this->assertSame(42, getParamNumberRange($request, 'price', 10, 100, 42));
    }

    public function testThrowsNotFoundExceptionWhenThrowable()
    {
        $this->expectException(NotFoundException::class);
        $request = $this->createRequest([]);
        getParamNumberRange($request, 'missing', 0, 100, null, [], HttpParamStrategy::BOTH, true);
    }

    /** @throws NotFoundException */
    public function testStrategyQueryVsBody()
    {
        $request = $this->createRequest(['val' => 10], ['val' => 20]);
        $this->assertSame(10, getParamNumberRange($request, 'val', 0, 100, null, [], HttpParamStrategy::QUERY));
        $this->assertSame(20, getParamNumberRange($request, 'val', 0, 100, null, [], HttpParamStrategy::BODY));
    }

    /** @throws NotFoundException */
    public function testGetParamIntRange()
    {
        $request = $this->createRequest(['qty' => '15']);
        $this->assertSame(15, getParamIntRange($request, 'qty', 10, 20));
        $this->assertSame(16, getParamIntRange($request, 'qty', 16, 20)); // clamp min
        $this->assertSame(14, getParamIntRange($request, 'qty', 10, 14)); // clamp max
    }

    /** @throws NotFoundException */
    public function testGetParamFloatRange()
    {
        $request = $this->createRequest(['rate' => '12.5']);
        $this->assertSame(12.5, getParamFloatRange($request, 'rate', 10.0, 20.0));
        $this->assertSame(13.0, getParamFloatRange($request, 'rate', 13.0, 20.0)); // clamp min
        $this->assertSame(12.0, getParamFloatRange($request, 'rate', 10.0, 12.0)); // clamp max
    }

    /** @throws NotFoundException */
    public function testNullRequestReturnsDefault()
    {
        $this->assertSame(42, getParamNumberRange(null, 'any', 0, 100, 42));
        $this->assertSame(42, getParamIntRange(null, 'any', 0, 100, 42));
        $this->assertSame(3.14, getParamFloatRange(null, 'any', 0, 10, 3.14));
    }
}