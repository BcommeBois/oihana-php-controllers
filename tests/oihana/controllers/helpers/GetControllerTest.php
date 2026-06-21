<?php

namespace tests\oihana\controllers\helpers ;

use Exception;
use PHPUnit\Framework\TestCase;

use oihana\controllers\Controller;
use oihana\controllers\enums\ControllerParam;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;


use function oihana\controllers\helpers\getController;

final class GetControllerTest extends TestCase
{
    private ContainerInterface $container;
    private Controller $mockController;

    protected function setUp(): void
    {
        $this->mockController = $this->createStub(Controller::class);
        $this->container      = $this->createStub(ContainerInterface::class);
    }

    /**
     * Test: Direct Controller instance is returned as-is
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testReturnsDirectControllerInstance(): void
    {
        $result = getController($this->mockController);
        $this->assertSame($this->mockController, $result);
    }

    /**
     * Test: Null definition with no default returns null
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testReturnsNullWhenDefinitionIsNull(): void
    {
        $result = getController(null);
        $this->assertNull($result);
    }

    /**
     * Test: Default controller is returned when definition is null
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testReturnsDefaultWhenDefinitionIsNull(): void
    {
        $defaultController = $this->createStub(Controller::class);
        $result = getController(null, null, $defaultController);
        $this->assertSame($defaultController, $result);
    }

    /**
     * Test: Array with CONTROLLER key extracts and returns the controller
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testExtractsControllerFromArray(): void
    {
        $definition = [ControllerParam::CONTROLLER => $this->mockController];
        $result = getController($definition);
        $this->assertSame($this->mockController, $result);
    }

    /**
     * Test: Array without CONTROLLER key returns default
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testArrayWithoutControllerKeyReturnsDefault(): void
    {
        $defaultController = $this->createStub(Controller::class);
        $definition = ['other_key' => 'value'];
        $result = getController($definition, null, $defaultController);
        $this->assertSame($defaultController, $result);
    }

    /**
     * Test: String identifier resolved from container
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testResolvesStringFromContainer(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('controller_id')->willReturn(true);
        $this->container->method('get')->with('controller_id')->willReturn($this->mockController);

        $result = getController('controller_id', $this->container);
        $this->assertSame($this->mockController, $result);
    }

    /**
     * Test: String not found in container returns default
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testReturnsDefaultWhenStringNotInContainer(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $defaultController = $this->createStub(Controller::class);
        $this->container->method('has')->with('controller_id')->willReturn(false);

        $result = getController('controller_id', $this->container, $defaultController);
        $this->assertSame($defaultController, $result);
    }

    /**
     * Test: String with no container returns default
     */
    public function testReturnsDefaultWhenContainerIsNull(): void
    {
        $defaultController = $this->createStub(Controller::class);
        $result = getController('controller_id', null, $defaultController);
        $this->assertSame($defaultController, $result);
    }

    /**
     * Test: Container returns non-Controller instance, returns default
     */
    public function testReturnsDefaultWhenContainerReturnsNonController(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $defaultController = $this->createStub(Controller::class);
        $this->container->method('has')->with('controller_id')->willReturn(true);
        $this->container->method('get')->with('controller_id')->willReturn('not a controller');

        $result = getController('controller_id', $this->container, $defaultController);
        $this->assertSame($defaultController, $result);
    }

    /**
     * Test: Array extracted value is resolved from container
     */
    public function testResolvesArrayExtractedValueFromContainer(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('controller_id')->willReturn(true);
        $this->container->method('get')->with('controller_id')->willReturn($this->mockController);

        $definition = [ControllerParam::CONTROLLER => 'controller_id'];
        $result = getController($definition, $this->container);
        $this->assertSame($this->mockController, $result);
    }

    /**
     * Test: Priority - direct instance takes precedence over all
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDirectInstanceTakesPrecedence(): void
    {
        $defaultController = $this->createStub(Controller::class);

        $result = getController($this->mockController, $this->container, $defaultController);
        $this->assertSame($this->mockController, $result);
    }

    /**
     * Test: Container exception is propagated
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testPropagatesContainerException(): void
    {
        $this->expectException( ContainerExceptionInterface::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('controller_id')->willReturn(true);
        $this->container->method('get')->willThrowException
        (
            new class extends Exception implements ContainerExceptionInterface {}
        );

        getController('controller_id', $this->container);
    }
}