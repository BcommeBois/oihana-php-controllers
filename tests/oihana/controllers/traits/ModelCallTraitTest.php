<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\ModelCallTrait;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface as Request;

final class ModelCallTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        // Public proxies are required because both hooks are protected no-ops.
        $this->mock = new class
        {
            use ModelCallTrait;

            public function callBefore( ?Request $request , array &$init ): void
            {
                $this->beforeModelCall( $request , $init );
            }

            public function callAfter( ?Request $request , array &$init , mixed &$result ): void
            {
                $this->afterModelCall( $request , $init , $result );
            }
        };
    }

    public function testBeforeModelCallIsANoOp(): void
    {
        $init = [ 'a' => 1 ];
        $this->mock->callBefore( null , $init );
        $this->assertSame( [ 'a' => 1 ] , $init );
    }

    public function testAfterModelCallIsANoOp(): void
    {
        $init   = [ 'a' => 1 ];
        $result = [ 'ok' => true ];
        $this->mock->callAfter( null , $init , $result );

        $this->assertSame( [ 'a' => 1 ]     , $init );
        $this->assertSame( [ 'ok' => true ] , $result );
    }
}
