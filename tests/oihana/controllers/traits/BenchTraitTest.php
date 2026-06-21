<?php

namespace tests\oihana\controllers\traits;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\BenchTrait;
use oihana\enums\Output;

final class BenchTraitTest extends TestCase
{

    private object $mock;

    protected function setUp(): void
    {
        // Classe anonyme utilisant le trait
        $this->mock = new class
        {
            use BenchTrait;

            public function prepareBench(?ServerRequestInterface $request, array $args = [], ?array &$params = null )
            {
                return $this->bench; // retour basé sur la valeur de bench
            }
        };
    }

    public function testInitializeBenchWithBoolean()
    {
        $result = $this->mock->initializeBench(true);
        $this->assertSame($this->mock, $result);
        $this->assertTrue($this->mock->bench);

        $this->mock->initializeBench(false);
        $this->assertFalse($this->mock->bench);
    }

    public function testInitializeBenchWithArray()
    {
        $this->mock->initializeBench([ControllerParam::BENCH => true]);
        $this->assertTrue($this->mock->bench);

        $this->mock->initializeBench();
        $this->assertFalse($this->mock->bench);
    }

    public function testStartBenchReturnsTimestampWhenBenchEnabled()
    {
        $this->mock->bench = true;

        $start = $this->mock->startBench(null);
        $this->assertIsFloat($start);
        $this->assertGreaterThan(0, $start);
    }

    public function testStartBenchReturnsZeroWhenBenchDisabled()
    {
        $this->mock->bench = false;

        $start = $this->mock->startBench(null);
        $this->assertSame(0, $start);
    }

    public function testEndBenchReturnsNullWhenNoTimestamp()
    {
        $options = [];
        $result = $this->mock->endBench(null, $options);
        $this->assertNull($result);
        $this->assertEmpty($options);
    }

    public function testEndBenchReturnsTimeInterval()
    {
        $options = [];
        $timestamp = microtime(true) - 1; // simule 1s avant

        $result = $this->mock->endBench($timestamp, $options);

        $this->assertIsString($result);
        $this->assertArrayHasKey(Output::TIME, $options);
        $this->assertSame($result, $options[Output::TIME]);
    }

    public function testStartAndEndBenchIntegration()
    {
        $this->mock->bench = true;

        $options = [];
        $start = $this->mock->startBench(null);
        usleep(100000); // 0.1s

        $end = $this->mock->endBench($start, $options);

        $this->assertIsString($end);
        $this->assertArrayHasKey(Output::TIME, $options);
    }
}