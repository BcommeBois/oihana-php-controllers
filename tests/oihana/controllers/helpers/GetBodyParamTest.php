<?php

namespace tests\oihana\controllers\helpers ;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

use function oihana\controllers\helpers\getBodyParam;

final class GetBodyParamTest extends TestCase
{
    public function testReturnsNullWhenRequestIsNull(): void
    {
        $this->assertNull(getBodyParam(null, 'foo'));
    }

    public function testReturnsNullWhenBodyIsEmpty(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);

        $this->assertNull( getBodyParam( $request , 'foo' ) ) ;
    }

    public function testReturnsValueForSimpleKey(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([
            'name' => 'Alice',
            'age'  => 30,
        ]);

        $this->assertSame('Alice', getBodyParam($request, 'name'));
        $this->assertSame(30, getBodyParam($request, 'age'));
    }

    public function testReturnsValueForNestedKeyUsingDotNotation(): void
    {
        $body = [
            'user' => [
                'profile' => [
                    'email' => 'test@example.com',
                    'active' => true,
                ],
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        $this->assertSame('test@example.com', getBodyParam($request, 'user.profile.email'));
        $this->assertTrue(getBodyParam($request, 'user.profile.active'));
    }

    public function testReturnsNullForMissingKey(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn(['foo' => 'bar']);

        $this->assertNull(getBodyParam($request, 'baz'));
        $this->assertNull(getBodyParam($request, 'foo.bar'));
    }

    public function testHandlesNestedArraysAndMixedTypes(): void
    {
        $body = [
            'geo' => [
                'latitude'  => 42.123,
                'longitude' => 1.456,
                'meta'      => ['alt' => null],
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        $this->assertSame(42.123, getBodyParam($request, 'geo.latitude'));
        $this->assertSame(1.456, getBodyParam($request, 'geo.longitude'));
        $this->assertNull(getBodyParam($request, 'geo.meta.alt'));
    }

    public function testCastsObjectsToArrayIfNeeded(): void
    {
        $body = (object)
        [
            'settings' => (object)[
                'theme' => 'dark',
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);

        // getParsedBody() est casté en array dans la fonction
        $this->assertSame('dark', getBodyParam($request, 'settings.theme'));
    }
}