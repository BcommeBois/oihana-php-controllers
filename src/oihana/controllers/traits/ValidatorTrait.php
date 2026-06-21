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
 * Trait ValidatorTrait
 * @see https://github.com/somnambulist-tech/validation
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
     * Register the extra validator's rules.
     * @param array $rules
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
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
     * @param ?Request $request Optional PSR-7 Request object.
     * @param ?Response $response The PSR-7 Response object.
     * @param Validation $validation
     * @param array $errors
     * @param int|string $code
     * @return Response|null
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
     * @return array
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
     * @param array $init
     *
     * @return static
     *
     * @throws DependencyException
     * @throws NotFoundException
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
     * @return array
     */
    public function prepareRules( ?string $method = null ) :array
    {
        return array_merge( $this->rules[ HttpMethod::ALL ] ?? [] , $this->rules[ $method ] ?? [] ) ;
    }
}