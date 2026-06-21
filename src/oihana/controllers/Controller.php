<?php

namespace oihana\controllers ;

use oihana\enums\http\HttpStatusCode;
use ReflectionException;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;

use oihana\controllers\traits\ApiTrait;
use oihana\controllers\traits\AppTrait;
use oihana\controllers\traits\BenchTrait;
use oihana\controllers\traits\ParamsStrategyTrait;
use oihana\controllers\traits\HttpCacheTrait;
use oihana\controllers\traits\JsonTrait;
use oihana\controllers\traits\MockTrait;
use oihana\controllers\traits\PaginationTrait;
use oihana\controllers\traits\PathTrait;
use oihana\controllers\traits\RouterTrait;
use oihana\controllers\traits\StatusTrait;
use oihana\controllers\traits\ValidatorTrait;
use oihana\enums\Char;
use oihana\enums\http\HttpHeader;
use oihana\logging\LoggerTrait;
use oihana\traits\ConfigTrait;
use oihana\traits\ContainerTrait;
use oihana\traits\ToStringTrait;

/**
 * Abstract base Controller for all application endpoints.
 *
 * Provides a unified foundation for controllers, integrating:
 * - Dependency injection (PSR-11 compatible)
 * - Routing context & helpers
 * - JSON / HTTP response utilities
 * - Validation, logging, configuration, and benchmarking
 * - Pagination, cache, and mock data management
 *
 * Extend this class to create your own route handlers.
 *
 * Example:
 * ```php
 * class UserController extends Controller
 * {
 *     public function list(Request $request, Response $response): Response
 *    {
 *         $users = $this->repository->findAll();
 *         return $this->jsonResponse($response, $users);
 *     }
 * }
 * ```
 *
 * @package oihana\controllers
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
abstract class Controller
{
    /**
     * Creates a new Controller instance.
     *
     * Initializes all traits and dependencies using the DI container.
     *
     * @param Container $container The DI container reference to initialize the controller.
     * @param array     $init      The optional properties to passed-in to initialize the object.
     *                              - string $path : The path expression of the component.
     *                              - Validator $validator : The optional validator of the controller (by default use a basic Validator instance).
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function __construct( Container $container , array $init = [] )
    {
        $this->container = $container ;
        $this->initializeApi            ( $init , $container )
             ->initializeApp            ( $init , $container )
             ->initializeBaseUrl        ( $init , $container )
             ->initializeCborOptions    ( $init , $container )
             ->initializeConfig         ( $init , $container )
             ->initializeHttpCache      ( $init , $container )
             ->initializeJsonOptions    ( $init , $container )
             ->initializeLoggable       ( $init , $container )
             ->initializeLogger         ( $init , $container )
             ->initializePagination     ( $init , $container )
             ->initializeRouterParser   ( $init , $container )
             ->initializeBench          ( $init )
             ->initializeMock           ( $init )
             ->initializePath           ( $init )
             ->initializeParamsStrategy ( $init )
             ->initializeValidator      ( $init ) ; // https://github.com/somnambulist-tech/validation
    }

    use ApiTrait            ,
        AppTrait            ,
        BenchTrait          ,
        ContainerTrait      ,
        ConfigTrait         ,
        HttpCacheTrait      ,
        JsonTrait           ,
        LoggerTrait         ,
        MockTrait           ,
        PaginationTrait     ,
        ParamsStrategyTrait ,
        PathTrait           ,
        RouterTrait         ,
        StatusTrait         ,
        ToStringTrait       ,
        ValidatorTrait      ;

    /**
     * Conditions or validation rules for the controller.
     */
    public ?array $conditions ;

    /**
     * The full path reference.
     */
    public string $fullPath ;

    /**
     * The path reference.
     */
    public string $path = Char::EMPTY ;

    /**
     * The path of an owner reference.
     * @var string|null
     */
    public ?string $ownerPath = Char::EMPTY ;

    /**
     * Returns allowed HTTP methods for the current route.
     *
     * @param ?Request $request Optional request.
     *
     * @return string[] List of allowed HTTP methods.
     */
    public function getAllowedMethods( ?Request $request ) :array
    {
        return isset( $request ) ? RouteContext::fromRequest( $request )->getRoutingResults()->getAllowedMethods() : [] ;
    }

    /**
     * Returns the current route associated with the request.
     *
     * @param ?Request $request
     *
     * @return RouteInterface|null
     */
    public function getRoute( ?Request $request ) :?RouteInterface
    {
        return isset( $request ) ? RouteContext::fromRequest( $request )->getRoute() : null ;
    }

    /**
     * Creates an HTTP redirect response.
     *
     * @param Response $response The PSR-7 response object.
     * @param string $url The target URL.
     * @param int $status Optional HTTP status code (default 302).
     *
     * @return Response The response with redirect headers.
     */
    public function redirectResponse( Response $response , string $url , int $status = HttpStatusCode::FOUND ) :Response
    {
        return $response->withHeader( HttpHeader::LOCATION , $url )->withStatus( $status ) ;
    }
}