<?php

namespace tests\oihana\controllers\helpers ;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

use function oihana\controllers\helpers\getBodyParams;

final class GetBodyParamsTest extends TestCase
{
    public function testReturnsEmptyArrayWhenRequestIsNull(): void
    {
        $this->assertSame([], getBodyParams(null, ['foo', 'bar']));
    }

    public function testReturnsEmptyArrayWhenBodyIsEmpty(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);

        $this->assertSame([], getBodyParams($request, ['foo', 'bar']));
    }

    public function testReturnsValuesForSimpleKeys(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn
        ([
            'name' => 'Alice',
            'age'  => 30,
        ]);

        $expected = [
            'name' => 'Alice',
            'age'  => 30,
        ];

        $this->assertSame($expected, getBodyParams($request, ['name', 'age']));
    }

    public function testReturnsValuesForNestedKeysUsingDotNotation(): void
    {
        $body = [
            'user' => [
                'profile' => [
                    'email' => 'a@b.c',
                    'active' => true,
                ],
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        $expected =
        [
            'user' =>
            [
                'profile' =>
                [
                    'email' => 'a@b.c',
                    'active' => true,
                ],
            ],
        ];

        $this->assertSame(
            $expected,
            getBodyParams($request, ['user.profile.email', 'user.profile.active'])
        );
    }

    public function testIgnoresMissingKeys(): void
    {
        $body = ['foo' => 'bar'];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        // Key exists → included, missing key → ignored
        $expected = ['foo' => 'bar'];

        $this->assertSame(
            $expected,
            getBodyParams($request, ['foo', 'missing'])
        );
    }

    public function testHandlesNestedArraysAndObjects(): void
    {
        $body = (object)[
            'geo' => [
                'latitude' => 42.123,
                'longitude' => 1.456,
                'meta' => (object)['alt' => null],
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        $expected = [
            'geo' => [
                'latitude' => 42.123,
                'longitude' => 1.456,
                'meta' => ['alt' => null],
            ],
        ];

        $result = getBodyParams($request, [
            'geo.latitude',
            'geo.longitude',
            'geo.meta.alt',
        ]);

        $this->assertSame($expected, $result);
    }

    public function testMixOfExistingAndMissingKeys(): void
    {
        $body = [
            'a' => 1,
            'b' => 2,
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        $expected = [
            'a' => 1,
        ];

        $this->assertSame(
            $expected,
            getBodyParams($request, ['a', 'missing'])
        );
    }

    public function testNestedObjectsConvertedToArrays(): void
    {
        $body = (object)[
            'user' => (object)[
                'profile' => (object)[
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        $expected = [
            'user' => [
                'profile' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ],
        ];

        $result = getBodyParams($request, ['user.profile.name', 'user.profile.email']);

        $this->assertSame($expected, $result);
    }
}