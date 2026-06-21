<?php

namespace oihana\controllers\traits ;

use oihana\enums\ServerParam;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;

use function oihana\core\strings\formatRequestArgs;
use function oihana\files\path\joinPaths;

/**
 * Provides helper methods for managing the application's base URL.
 * This trait allows you to:
 * - dynamically initialize the base URL from an options array or a DI container,
 * - generate full or partial URLs based on the base URL,
 * - build URLs for named routes.
 *
 * Note:
 * You can define a `baseUrl` in your DI container to be used across all controllers.
 *
 * @package oihana\controllers\traits
 */
trait BaseUrlTrait
{
    /**
     * The application's base URL.
     * Used as a prefix for all generated URLs.
     *
     * @var string
     */
    public string $baseUrl = Char::EMPTY ;

    /**
     * Initializes the internal `baseUrl` property.
     *
     * The value can come from:
     * - the `$init` array (key `ControllerParam::BASE_URL`),
     * - the DI container if provided and contains the key `ControllerParam::BASE_URL`,
     * - otherwise it remains an empty string.
     *
     * @param array $init Optional initialization array
     * @param ContainerInterface|null $container Optional DI container to fetch the base URL
     *
     * @return static Returns the current instance for method chaining
     *
     * @throws ContainerExceptionInterface If the container encounters an error during access
     * @throws NotFoundExceptionInterface If the `ControllerParam::BASE_URL` key is not found in the container
     */
    public function initializeBaseUrl( array $init = [] , ?ContainerInterface $container = null ):static
    {
        $baseUrl = $init[ ControllerParam::BASE_URL ] ?? null;

        if( empty( $baseUrl ) && $container instanceof ContainerInterface && $container->has( ControllerParam::BASE_URL ) )
        {
            $baseUrl = $container->get( ControllerParam::BASE_URL ) ;
        }

        $this->baseUrl = is_string( $baseUrl ) ? $baseUrl : Char::EMPTY ;

        return $this ;
    }

    /**
     * Returns the current application path relative to the base URL.
     *
     * Uses the `Request` object if provided, otherwise falls back to `$_SERVER['REQUEST_URI']`.
     * Allows adding GET parameters via `$params`.
     *
     * @param Request|null $request Optional HTTP request
     * @param array $params Optional associative array of GET parameters
     * @param bool $useNow If true, adds a `_` parameter with the current timestamp to prevent caching
     *
     * @return string Full path including the base URL and query parameters
     */
    public function getCurrentPath( ?Request $request = null , array $params = [] , bool $useNow = false ) :string
    {
        $path = isset( $request ) ? $request->getUri()->getPath() : parse_url( $_SERVER[ ServerParam::REQUEST_URI ] , PHP_URL_PATH ) ;
        return joinPaths( $this->baseUrl , $path ) . formatRequestArgs( $params , $useNow ) ;
    }

    /**
     * Returns the full application URL including the base URL and optional parameters.
     *
     * @param array|null $params Optional associative array of GET parameters
     * @param bool $useNow If true, adds a `_` parameter with the current timestamp
     * @return string Full URL
     */
    public function getFullPath( ?array $params = null , bool $useNow = false ) :string
    {
        return $this->baseUrl . formatRequestArgs( is_array( $params ) ? $params : [] , $useNow ) ;
    }

    /**
     * Generates a path based on the base URL and a provided relative path.
     *
     * @param string $path Relative path to append to the base URL
     * @param array|null $params Optional associative array of GET parameters
     * @param bool $useNow If true, adds a `_` parameter with the current timestamp
     * @return string Full path
     */
    public function getPath( string $path = Char::EMPTY , ?array $params = null , bool $useNow = false ) :string
    {
        return joinPaths( $this->baseUrl . $path ) . formatRequestArgs( is_array($params) ? $params : [] , $useNow ) ;
    }

}