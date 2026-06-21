<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\MockTrait;

use PHPUnit\Framework\TestCase;

final class MockTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use MockTrait;
        };
    }

    public function testInitializeMockWithBooleanTrue(): void
    {
        $result = $this->mock->initializeMock( true );

        $this->assertSame( $this->mock , $result );
        $this->assertTrue( $this->mock->mock );
    }

    public function testInitializeMockWithBooleanFalse(): void
    {
        $this->mock->initializeMock( false );
        $this->assertFalse( $this->mock->mock );
    }

    public function testInitializeMockFromArray(): void
    {
        $this->mock->initializeMock([ ControllerParam::MOCK => true ]);
        $this->assertTrue( $this->mock->mock );
    }

    public function testInitializeMockFromArrayWithoutKeyIsNull(): void
    {
        $this->mock->initializeMock([ 'other' => 1 ]);
        $this->assertNull( $this->mock->mock );
    }

    public function testInitializeMockDefaultIsNull(): void
    {
        $this->mock->initializeMock();
        $this->assertNull( $this->mock->mock );
    }
}
