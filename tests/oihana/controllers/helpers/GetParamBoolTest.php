<?php

namespace oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\Boolean;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

final class GetParamBoolTest extends TestCase
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
    public function testReturnsTrueForBooleanTrue()
    {
        $request = $this->createRequest(['active' => Boolean::TRUE]);
        $this->assertTrue(getParamBool($request, 'active'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsFalseForBooleanFalse()
    {
        $request = $this->createRequest(['active' => Boolean::FALSE]);
        $this->assertFalse(getParamBool($request, 'active'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsDefaultForMissingKey()
    {
        $request = $this->createRequest([]);
        $this->assertTrue(getParamBool($request, 'active', [], true));
        $this->assertFalse(getParamBool($request, 'active', [], false));
        $this->assertNull(getParamBool($request, 'active'));
    }

    public function testThrowsNotFoundExceptionWhenThrowable()
    {
        $this->expectException(NotFoundException::class);
        $request = $this->createRequest([]);
        getParamBool($request, 'missing', [], null, HttpParamStrategy::BOTH, true);
    }

    /**
     * @throws NotFoundException
     */
    public function testSearchesQueryWhenStrategyQuery()
    {
        $request = $this->createRequest(['flag' => Boolean::TRUE], ['flag' => Boolean::FALSE]);
        $this->assertTrue(getParamBool($request, 'flag', [], null, HttpParamStrategy::QUERY));
    }

    /**
     * @throws NotFoundException
     */
    public function testSearchesBodyWhenStrategyBody()
    {
        $request = $this->createRequest(['flag' => Boolean::FALSE], ['flag' => Boolean::TRUE]);
        $this->assertTrue(getParamBool($request, 'flag', [], null, HttpParamStrategy::BODY));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsBooleanForStringAndNumericValues()
    {
        $request = $this->createRequest([
            'a' => 'true',
            'b' => 'false',
            'c' => '1',
            'd' => '0',
            'e' => 'yes',
            'f' => 'no',
            'g' => 'on',
            'h' => 'off',
            'i' => 1,
            'j' => 0
        ]);

        $this->assertTrue(getParamBool($request, 'a'));
        $this->assertFalse(getParamBool($request, 'b'));
        $this->assertTrue(getParamBool($request, 'c'));
        $this->assertFalse(getParamBool($request, 'd'));
        $this->assertTrue(getParamBool($request, 'e'));
        $this->assertFalse(getParamBool($request, 'f'));
        $this->assertTrue(getParamBool($request, 'g'));
        $this->assertFalse(getParamBool($request, 'h'));
        $this->assertTrue(getParamBool($request, 'i'));
        $this->assertFalse(getParamBool($request, 'j'));
    }

    /**
     * @throws NotFoundException
     */
    public function testReturnsNullWhenRequestIsNull()
    {
        $this->assertNull(getParamBool(null, 'any'));
        $this->assertTrue(getParamBool(null, 'any', [], true));
        $this->assertFalse(getParamBool(null, 'any', [], false));
    }


}