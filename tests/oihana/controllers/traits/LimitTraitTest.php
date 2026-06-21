<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\LimitTrait;
use PHPUnit\Framework\TestCase;

use xyz\oihana\schema\Pagination;

/**
 * Dummy class using LimitTrait for testing.
 */
class LimitTraitMock
{
    use LimitTrait;
}

final class LimitTraitTest extends TestCase
{
    protected LimitTraitMock $mock;

    protected function setUp(): void
    {
        $this->mock = new LimitTraitMock();
    }

    public function testInitializeLimitSetsAllValues()
    {
        $init = [
            Pagination::LIMIT     => 25,
            Pagination::MAX_LIMIT => 200,
            Pagination::MIN_LIMIT => 5,
            Pagination::OFFSET    => 50,
        ];

        $result = $this->mock->initializeLimit($init);

        $this->assertSame($this->mock, $result);
        $this->assertSame(25, $this->mock->limit);
        $this->assertSame(200, $this->mock->maxLimit);
        $this->assertSame(5, $this->mock->minLimit);
        $this->assertSame(50, $this->mock->offset);
    }

    public function testInitializeLimitKeepsExistingValuesWhenNotOverridden()
    {
        $this->mock->limit    = 10;
        $this->mock->maxLimit = 100;
        $this->mock->minLimit = 1;
        $this->mock->offset   = 0;

        $this->mock->initializeLimit
        ([
            Pagination::LIMIT => 20, // Only override limit
        ]);

        $this->assertSame(20, $this->mock->limit);
        $this->assertSame(100, $this->mock->maxLimit);
        $this->assertSame(1, $this->mock->minLimit);
        $this->assertSame(0, $this->mock->offset);
    }

    public function testInitializeLimitWithEmptyArrayDoesNotChangeValues()
    {
        $this->mock->limit    = 10;
        $this->mock->maxLimit = 100;
        $this->mock->minLimit = 1;
        $this->mock->offset   = 5;

        $this->mock->initializeLimit();

        $this->assertSame(10  , $this->mock->limit    ) ;
        $this->assertSame(100 , $this->mock->maxLimit ) ;
        $this->assertSame(1   , $this->mock->minLimit ) ;
        $this->assertSame(5   , $this->mock->offset   ) ;
    }

    public function testInitializeLimitHandlesNullValues()
    {
        $this->mock->initializeLimit
        ([
            Pagination::LIMIT     => null,
            Pagination::MAX_LIMIT => null,
            Pagination::MIN_LIMIT => null,
            Pagination::OFFSET    => null,
        ]);

        $this->assertNull( $this->mock->limit    );
        $this->assertNull( $this->mock->maxLimit );
        $this->assertNull( $this->mock->minLimit );
        $this->assertNull( $this->mock->offset   );
    }
}