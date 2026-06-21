<?php

namespace oihana\controllers\traits ;

use Psr\Http\Message\ResponseInterface as Response;

use DI\DependencyException;
use DI\NotFoundException;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Output;
use oihana\enums\http\HttpMethod;
use oihana\traits\ContainerTrait;

use Psr\Http\Message\ServerRequestInterface as Request;
use Somnambulist\Components\Validation\Factory as Validator;
use Somnambulist\Components\Validation\Rule;
use Somnambulist\Components\Validation\Validation;

/**
 * Provides helper methods for validation and error handling within a controller.
 *
 * This trait wraps the Somnambulist validation factory: it holds the validator instance,
 * the base and custom rule definitions, registers extra rules on the validator and turns
 * validation failures into PSR-7 error responses.
 *
 * @see https://github.com/somnambulist-tech/validation
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ValidatorTrait
{
    use ContainerTrait ,
        StatusTrait ;

    /**
     * The custom validation rules definitions.
     * @var array
     */
    public array $customRules = [] ;

    /**
     * The rules definitions used in the prepareRules method to initialize a validation process in the POST/PATCH/PUT and custom methods.
     * @var array
     * @see More informations in the the prepareRules method definition.
     */
    public array $rules = [] ;

    /**
     * The validator reference.
     * @var Validator
     */
    public Validator $validator ;

    /**
     * Registers extra rules on the internal validator.
     *
     * Each entry maps a rule name to a {@see Rule} instance. A string value is resolved
     * from the DI container when it references a known entry before being registered.
     *
     * @param array $rules An associative array of `ruleName => Rule|string` definitions to register.
     *
     * @return void
     *
     * @throws DependencyException If the dependency cannot be resolved by the container.
     * @throws NotFoundException If the requested entry is not found in the container.
     */
    public function addRules( array $rules = [] ):void
    {
        if( !empty( $rules ) )
        {
            foreach( $rules as $key => $rule )
            {
                if( is_string( $rule ) && $this->container->has( $rule ) )
                {
                    $rule = $this->container->get( $rule ) ;
                }
                if( $rule instanceof Rule )
                {
                    $this->validator->addRule( $key , $rule );
                }
            }
        }
    }

    /**
     * Returns an error if the validator fails.
     *
     * @param ?Request   $request    Optional PSR-7 Request object.
     * @param ?Response  $response   The PSR-7 Response object.
     * @param Validation $validation The validation result to inspect for failures.
     * @param array      $errors     Optional initial errors merged with the validation errors.
     * @param int|string $code       The HTTP status code of the error response (defaults to 400).
     *
     * @return Response|null The failure response, or null when no response object was provided.
     */
    public function getValidatorError
    (
        ?Request   $request     ,
        ?Response  $response    ,
        Validation $validation  ,
        array      $errors = [] ,
        int|string $code = 400
    )
    : ?Response
    {
        if( $validation->fails() )
        {
            $errors = [ ...$errors , ...$validation->errors()->firstOfAll() ] ;
        }
        return $this->fail
        (
            $request  ,
            $response ,
            $code     ,
            null ,
            [ Output::ERRORS => $errors ]
        ) ;
    }

    /**
     * Returns the list of all extra-rules to initialize the validator.
     * Overrides this method to extends the default rules definitions.
     *
     * @return array The custom validation rules to register on the validator.
     */
    public function initCustomValidationRules() :array
    {
        return $this->customRules ;
    }

    /**
     * Sets the current internal validator of the controller.
     *
     * By default, creates a new Validator instance and initialize it.
     *
     * @param array $init Initialization array, read from the {@see ControllerParam::VALIDATOR}, {@see ControllerParam::CUSTOM_RULES} and {@see ControllerParam::RULES} keys.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws DependencyException If the dependency cannot be resolved by the container.
     * @throws NotFoundException If the requested entry is not found in the container.
     */
    public function initializeValidator( array $init = [] ) :static
    {
        $validator = $init[ ControllerParam::VALIDATOR ] ?? null ;

        $this->validator   = $validator instanceof Validator ? $validator : new Validator() ;
        $this->customRules = $init[ ControllerParam::CUSTOM_RULES ] ?? $this->customRules ;
        $this->rules       = $init[ ControllerParam::RULES        ] ?? $this->rules ;

        $this->addRules( $this->initCustomValidationRules() ) ;

        return $this ;
    }

    /**
     * Merge the default common and the specific method's rules.
     *
     * You can overrides this method to prepare the validator rules with a specific router method and strategy.
     *
     * @param ?string $method The specific rule type to override the default rules definitions.
     *
     * @return array The merged rules, combining the {@see HttpMethod::ALL} rules with the method-specific ones.
     */
    public function prepareRules( ?string $method = null ) :array
    {
        return array_merge( $this->rules[ HttpMethod::ALL ] ?? [] , $this->rules[ $method ] ?? [] ) ;
    }
}