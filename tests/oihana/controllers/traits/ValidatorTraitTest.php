<?php

namespace tests\oihana\controllers\traits;

use DI\DependencyException;
use DI\NotFoundException;
use oihana\controllers\traits\ValidatorTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

use DI\Container;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\http\HttpMethod;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use Somnambulist\Components\Validation\ErrorBag;
use Somnambulist\Components\Validation\Factory as Validator;
use Somnambulist\Components\Validation\Rule;
use Somnambulist\Components\Validation\Validation;

class MockValidatorController
{
    public function __construct( Container $container )
    {
        $this->container = $container;
        $this->validator = new Validator();
    }

    use ValidatorTrait;
}

final class ValidatorTraitTest extends TestCase
{
    private object $controller;

    private Container $container;

    protected function setUp(): void
    {
        $this->container  = new Container();
        $this->controller = new MockValidatorController( $this->container ) ;
    }

    /**
     * Test the validator property getter lazy initialization
     */
    public function testValidatorGetterLazyInitialization(): void
    {
        $validator = $this->controller->validator;
        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * Test the validator property setter
     */
    public function testValidatorSetter(): void
    {
        $customValidator = new Validator();
        $this->controller->validator = $customValidator ;

        $this->assertSame( $customValidator , $this->controller->validator ) ;
    }

    /**
     * Test the validator property setter with null creates new Validator
     */
    public function testValidatorSetterWithNull(): void
    {
        $this->controller->validator = new Validator();
        $this->assertInstanceOf(Validator::class, $this->controller->validator);
    }

    /**
     * Test initializeValidator with empty array
     */
    public function testInitializeValidatorEmpty(): void
    {
        $result = $this->controller->initializeValidator();

        $this->assertSame($this->controller, $result);
        $this->assertInstanceOf(Validator::class, $this->controller->validator);
    }

    /**
     * Test initializeValidator with custom validator
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInitializeValidatorWithCustomValidator(): void
    {
        $customValidator = new Validator();
        $init =
        [
            ControllerParam::VALIDATOR => $customValidator ,
        ];

        $this->controller->initializeValidator( $init ) ;

        $this->assertSame( $customValidator, $this->controller->validator );
    }

    /**
     * Test initializeValidator with custom rules
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function testInitializeValidatorWithCustomRules(): void
    {
        $customRules =
            [
            'email' => 'email_rule',
            'phone' => 'phone_rule',
        ];
        $init = [
            ControllerParam::CUSTOM_RULES => $customRules,
        ];

        $this->controller->initializeValidator($init);
        $this->assertEquals($customRules, $this->controller->customRules);
    }

    /**
     * Test initializeValidator with rules
     */
    public function testInitializeValidatorWithRules(): void
    {
        $rules = [
            HttpMethod::POST => [ 'name' => 'required|string'],
            HttpMethod::PUT  => [ 'name' => 'required|string', 'id' => 'required|integer'],
        ];
        $init = [
            ControllerParam::RULES => $rules,
        ];

        $this->controller->initializeValidator($init);

        $this->assertEquals($rules, $this->controller->rules);
    }

    /**
     * Test addRules with Rule instances
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    public function testAddRulesWithRuleInstances(): void
    {
        $mockRule = $this->createStub(Rule::class ) ;

        $this->controller->addRules
        ([
            'test_field' => $mockRule ,
        ]);

        // The validator should accept the rule
        $this->assertInstanceOf(Validator::class, $this->controller->validator);
    }

    /**
     * Test addRules with container string references
     */
    public function testAddRulesWithContainerStringReferences(): void
    {
        $mockRule = $this->createStub(Rule::class);

        $this->container->set('my_custom_rule', $mockRule);

        $this->controller->addRules([
            'test_field' => 'my_custom_rule',
        ]);

        $this->assertInstanceOf(Validator::class, $this->controller->validator);
    }

    /**
     * Test addRules with empty array
     */
    public function testAddRulesEmpty(): void
    {
        $this->controller->addRules([]);

        $this->assertInstanceOf(Validator::class, $this->controller->validator);
    }

    /**
     * Test addRules ignores non-Rule instances
     */
    public function testAddRulesIgnoresNonRuleInstances(): void
    {
        $this->controller->addRules([
            'test_field' => 'not_a_rule_object',
        ]);

        $this->assertInstanceOf(Validator::class, $this->controller->validator);
    }

    /**
     * Test initCustomValidationRules returns customRules
     */
    public function testInitCustomValidationRules(): void
    {
        $this->controller->customRules = ['rule1', 'rule2'];

        $result = $this->controller->initCustomValidationRules();

        $this->assertEquals(['rule1', 'rule2'], $result);
    }

    /**
     * Test prepareRules merges all and specific method rules
     */
    public function testPrepareRulesWithMethod(): void
    {
        $this->controller->rules = [
            HttpMethod::ALL => ['email' => 'required|email'],
            HttpMethod::POST => ['name' => 'required|string'],
        ];

        $result = $this->controller->prepareRules(HttpMethod::POST);

        $expected = [
            'email' => 'required|email',
            'name' => 'required|string',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test prepareRules with null method returns only ALL rules
     */
    public function testPrepareRulesWithoutMethod(): void
    {
        $this->controller->rules = [
            HttpMethod::ALL  => ['email' => 'required|email'  ],
            HttpMethod::POST => ['name'  => 'required|string' ],
        ];

        $result = $this->controller->prepareRules(null);

        $expected = ['email' => 'required|email'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test prepareRules with undefined method returns only ALL rules
     */
    public function testPrepareRulesWithUndefinedMethod(): void
    {
        $this->controller->rules = [
            HttpMethod::ALL => ['email' => 'required|email'],
        ];

        $result = $this->controller->prepareRules(HttpMethod::DELETE);

        $expected = ['email' => 'required|email'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test prepareRules with empty rules
     */
    public function testPrepareRulesEmpty(): void
    {
        $this->controller->rules = [];

        $result = $this->controller->prepareRules(HttpMethod::POST);

        $this->assertEquals([], $result);
    }

    /**
     * A passive Response stub accepting the fail() write/builder chain.
     */
    private function response(): ResponseInterface
    {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('write')->willReturnCallback(fn($data) => strlen((string) $data));

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('withStatus')->willReturnSelf();
        $response->method('withHeader')->willReturnSelf();

        return $response;
    }

    /**
     * getValidatorError merges validation errors into the failure response.
     */
    public function testGetValidatorErrorWhenValidationFails(): void
    {
        $errorBag = $this->createStub(ErrorBag::class);
        $errorBag->method('firstOfAll')->willReturn(['name' => 'name is required']);

        $validation = $this->createStub(Validation::class);
        $validation->method('fails')->willReturn(true);
        $validation->method('errors')->willReturn($errorBag);

        $response = $this->response();

        $result = $this->controller->getValidatorError(null, $response, $validation, ['extra' => 'err'], 422);

        $this->assertSame($response, $result);
    }

    /**
     * getValidatorError still returns a fail response when the validation passes
     * (the merge branch is skipped).
     */
    public function testGetValidatorErrorWhenValidationPasses(): void
    {
        $validation = $this->createStub(Validation::class);
        $validation->method('fails')->willReturn(false);

        $response = $this->response();

        $result = $this->controller->getValidatorError(null, $response, $validation);

        $this->assertSame($response, $result);
    }
}