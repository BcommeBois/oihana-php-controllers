<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\RedirectsTrait;

use PHPUnit\Framework\TestCase;

final class RedirectsTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use RedirectsTrait;
        };
    }

    public function testInitializeRedirectsFromInit(): void
    {
        $redirects = [ '/old' => '/new' ];
        $this->mock->initializeRedirects([ ControllerParam::REDIRECTS => $redirects ]);

        $this->assertSame( $redirects , $this->mock->redirects );
    }

    public function testInitializeRedirectsDefaultsToEmpty(): void
    {
        $this->mock->initializeRedirects();
        $this->assertSame( [] , $this->mock->redirects );
    }
}
