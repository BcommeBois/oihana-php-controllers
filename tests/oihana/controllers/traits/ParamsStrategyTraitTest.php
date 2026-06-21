<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\ParamsStrategyTrait;
use oihana\enums\http\HttpParamStrategy;
use PHPUnit\Framework\TestCase;

final class ParamsStrategyTraitTest extends TestCase
{
    use ParamsStrategyTrait;

    protected function setUp(): void
    {
        $this->paramsStrategy = HttpParamStrategy::BOTH ;
    }

    public function testInitializeParamsStrategy()
    {
        $this->initializeParamsStrategy(HttpParamStrategy::BODY);
        $this->assertSame(HttpParamStrategy::BODY, $this->paramsStrategy);

        $this->initializeParamsStrategy([ self::PARAMS_STRATEGY => HttpParamStrategy::QUERY]);
        $this->assertSame(HttpParamStrategy::QUERY, $this->paramsStrategy);

        // invalid strategy should keep previous
        $this->initializeParamsStrategy('invalid');
        $this->assertSame(HttpParamStrategy::QUERY, $this->paramsStrategy);
    }

}