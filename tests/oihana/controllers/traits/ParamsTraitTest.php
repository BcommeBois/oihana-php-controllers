<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\ParamsTrait;
use PHPUnit\Framework\TestCase;

use oihana\controllers\enums\ControllerParam;

final class ParamsTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use ParamsTrait;
        };
    }

    public function testInitializeParamsFromInit()
    {
        $init = [
            ControllerParam::PARAMS => ['foo' => 'bar', 'baz' => 123],
        ];

        $result = $this->mock->initializeParams($init);

        $this->assertSame($this->mock, $result);
        $this->assertIsArray($this->mock->params);
        $this->assertSame(['foo' => 'bar', 'baz' => 123], $this->mock->params);
    }

    public function testInitializeParamsFallbackToExistingValue()
    {
        $this->mock->params = ['existing' => 'value'];

        $result = $this->mock->initializeParams([]);

        $this->assertSame($this->mock, $result);
        $this->assertSame(['existing' => 'value'], $this->mock->params);
    }

    public function testInitializeParamsWithEmptyInitAndNullExisting()
    {
        $this->mock->params = null;

        $result = $this->mock->initializeParams([]);

        $this->assertSame($this->mock, $result);
        $this->assertNull($this->mock->params);
    }
}