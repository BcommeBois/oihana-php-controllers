<?php

namespace tests\oihana\controllers\helpers ;

use DI\NotFoundException;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;
use function oihana\controllers\helpers\getParamI18n;

class GetParamI18nTest extends TestCase
{
    /**
     * @throws NotFoundException
     */
    public function testRetrieveFromQuery(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'description' => ['fr' => 'Bonjour', 'en' => 'Hello', 'de' => 'Hallo']
        ]);
        $request->method('getParsedBody')->willReturn([]);

        $result = getParamI18n($request, 'description', [], ['fr','en']);
        $this->assertSame(['fr' => 'Bonjour', 'en' => 'Hello'], $result);
    }

    /**
     * @throws NotFoundException
     */
    public function testRetrieveFromBody(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn([
            'description' => ['fr' => 'Salut', 'en' => 'Hi', 'de' => 'Hallo']
        ]);

        $result = getParamI18n($request, 'description', [], ['fr','en']);
        $this->assertSame(['fr' => 'Salut', 'en' => 'Hi'], $result);
    }

    /**
     * @throws NotFoundException
     */
    public function testSanitizeCallbackApplied(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'description' => ['fr' => '<b>Bonjour</b>', 'en' => '<b>Hello</b>']
        ]);
        $request->method('getParsedBody')->willReturn([]);

        $result = getParamI18n(
            $request,
            'description',
            [],
            ['fr','en'],
            fn($v,$lang) => strip_tags($v)
        );

        $this->assertSame(['fr' => 'Bonjour', 'en' => 'Hello'], $result);
    }

    /**
     * @throws NotFoundException
     */
    public function testDefaultValueUsed(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn([]);

        $default = ['description' => ['fr' => 'Def FR', 'en' => 'Def EN']];
        $result = getParamI18n($request, 'description', $default, ['fr','en']);
        $this->assertSame(['fr' => 'Def FR', 'en' => 'Def EN'], $result);
    }

    public function testThrowableException(): void
    {
        $this->expectException( NotFoundException::class);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn([]);

        getParamI18n( $request, 'description', [], ['fr','en'], null, HttpParamStrategy::BOTH, true);
    }

    /**
     * @throws NotFoundException
     */
    public function testNullRequest(): void
    {
        $default = ['description' => ['fr' => 'Def FR']];
        $result = getParamI18n(null, 'description', $default, ['fr','en']);
        $this->assertSame(['fr' => 'Def FR', 'en' => null], $result);
    }

    /**
     * @throws NotFoundException
     */
    public function testFilterLanguagesEmpty(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);

        $request->method('getQueryParams')->willReturn
        ([
            'description' => ['de' => 'Hallo'] // language not allowed
        ]);

        $request->method('getParsedBody')->willReturn([]);

        $result = getParamI18n($request, 'description', ['fr'], ['fr','en']);
        $this->assertSame(['fr' => null, 'en' => null], $result);
    }
}