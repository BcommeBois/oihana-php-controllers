<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\JsonTrait;
use oihana\enums\JsonParam;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\http\HttpHeader;
use oihana\files\enums\FileMimeType;

use PHPUnit\Framework\TestCase;

final class JsonTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use JsonTrait;
        };
    }

    public function testInitializeJsonOptionsFromInit()
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

        $result = $this->mock->initializeJsonOptions([
            ControllerParam::JSON_OPTIONS => $flags
        ]);

        $this->assertSame($this->mock, $result);
        $this->assertSame($flags, $this->mock->jsonOptions);
    }

    public function testInitializeJsonOptionsFromContainer()
    {
        $flags = JSON_NUMERIC_CHECK | JSON_FORCE_OBJECT;

        $container = $this->createMock(ContainerInterface::class ) ;
        $container->method('has')->with(ControllerParam::JSON_OPTIONS)->willReturn(true);
        $container->method('get')->with(ControllerParam::JSON_OPTIONS)->willReturn($flags);

        $this->mock->initializeJsonOptions([], $container);

        $this->assertSame($flags, $this->mock->jsonOptions);
    }

    public function testInitializeJsonOptionsInvalidFlags()
    {
        $invalidFlags = 0xFFFF; // non valides
        $this->mock->initializeJsonOptions([ControllerParam::JSON_OPTIONS => $invalidFlags]);

        $this->assertSame(JsonParam::JSON_NONE, $this->mock->jsonOptions);
    }

    public function testInitializeJsonOptionsFallbackToNone()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ControllerParam::JSON_OPTIONS)->willReturn(false);

        $this->mock->initializeJsonOptions([], $container);

        $this->assertSame(JsonParam::JSON_NONE, $this->mock->jsonOptions);
    }

    public function testInitializeJsonSerializeOptionsFromContainer()
    {
        $options = [ 'reduce' => false , 'custom' => 1 ];

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback
        (
            fn( $key ) => $key === ControllerParam::JSON_SERIALIZE_OPTIONS
        );
        $container->method('get')->willReturn($options);

        // Passing an empty array forces the "empty($options)" container lookup branch.
        $this->mock->initializeJsonOptions
        (
            [ ControllerParam::JSON_SERIALIZE_OPTIONS => [] ],
            $container
        );

        $this->assertSame($options, $this->mock->jsonSerializeOptions);
    }

    public function testJsonResponseWritesJsonAndSetsHeaders()
    {
        $data = ['foo' => 'bar'];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('write')
            ->with(json_encode($data, $this->mock->jsonOptions));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $response->expects($this->once())
            ->method('withStatus')
            ->with(201)
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withHeader')
            ->with(HttpHeader::CONTENT_TYPE, FileMimeType::JSON)
            ->willReturnSelf();

        $result = $this->mock->jsonResponse($response, $data, 201);

        $this->assertSame($response, $result);
    }

}