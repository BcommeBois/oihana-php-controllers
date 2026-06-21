<?php

namespace oihana\controllers\traits;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use Slim\HttpCache\CacheProvider;

/**
 * Trait providing helpers to manage HTTP caching in controllers.
 *
 * This trait allows controllers to:
 *   - Optionally initialize an internal HTTP cache provider (`Slim\HttpCache\CacheProvider`).
 *   - Set or remove cache-related headers (`ETag`, `Last-Modified`, `Cache-Control`).
 *
 * **Important:**
 *   To enable caching, you **must** call {@see initializeHttpCache()} in your controller.
 *   If you forget to initialize the cache provider, these methods will silently
 *   return the original response without adding any cache-related headers.
 */
trait HttpCacheTrait
{
    /**
     * The cache provider reference (optional).
     *
     * When set, this property holds a `Slim\HttpCache\CacheProvider` instance
     * used to modify HTTP response headers for caching.
     *
     * @var CacheProvider|null
     */
    protected ?CacheProvider $httpCache = null;

    /**
     * Enable HTTP caching for the given response.
     *
     * This method sets a `Cache-Control` header using the underlying CacheProvider.
     * If the HTTP cache provider is **not** initialized, the response is returned unchanged.
     *
     * @param ResponseInterface   $response        A PSR-7 response object
     * @param string             $type            Cache-Control type: "private" or "public"
     * @param int|string|null    $maxAge          Maximum cache age in seconds or a datetime string parsable by strtotime()
     * @param bool               $mustRevalidate  Whether to add the "must-revalidate" directive
     *
     * @return ResponseInterface A new response object with cache headers if the cache provider is available.
     */
    public function allowCache
    (
        ResponseInterface $response       ,
        string            $type           = 'private',
        int|string|null   $maxAge         = null,
        bool              $mustRevalidate = false
    )
    : ResponseInterface
    {
        return $this->httpCache?->allowCache( $response , $type , $maxAge , $mustRevalidate ) ?? $response ;
    }

    /**
     * Enforce the removal of browser cache for a response.
     *
     * Equivalent to setting headers like `Cache-Control: no-store, no-cache, must-revalidate`.
     * If the HTTP cache provider is **not** initialized, the response is returned unchanged.
     *
     * @param ResponseInterface $response A PSR-7 response object
     *
     * @return ResponseInterface A new response object with cache denial headers if available.
     */
    public function denyCache(ResponseInterface $response): ResponseInterface
    {
        return $this->httpCache?->denyCache($response) ?? $response ;
    }

    /**
     * Initialize the internal HTTP cache provider.
     *
     * Priority order:
     * 1. `$init[ControllerParam::HTTP_CACHE]`
     * 2. `$container->get(CacheProvider::class)` if available in DI
     *
     * @param array $init Optional initialization array
     * @param ContainerInterface|null $container Optional DI container to retrieve the cache provider
     *
     * @return static Returns the current instance for method chaining
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function initializeHttpCache( array $init = [] , ?ContainerInterface $container = null ):static
    {
        $httpCache = $init[ ControllerParam::HTTP_CACHE ] ?? null;

        if( $httpCache === null && $container instanceof ContainerInterface && $container->has( CacheProvider::class ) )
        {
            $httpCache = $container->get( CacheProvider::class ) ;
        }

        if ( $httpCache instanceof CacheProvider )
        {
            $this->httpCache = $httpCache;
        }

        return $this ;
    }

    /**
     * Add an `ETag` header to a PSR-7 response object.
     *
     * The `ETag` is used by browsers and proxies for cache validation.
     * If the HTTP cache provider is **not** initialized, the response is returned unchanged.
     *
     * @param ResponseInterface $response A PSR-7 response object
     * @param string            $value    The ETag value
     * @param string            $type     The ETag type: either `"strong"` or `"weak"`
     *
     * @return ResponseInterface A new response object with the `ETag` header set if available
     */
    public function withEtag( ResponseInterface $response, string $value, string $type = 'strong' ): ResponseInterface
    {
        return $this->httpCache?->withEtag( $response , $value , $type ) ?? $response ;
    }

    /**
     * Add an `Expires` header to a PSR-7 response object.
     *
     * This header specifies the date and time after which the response is considered stale.
     * If the HTTP cache provider is **not** initialized, the response is returned unchanged.
     *
     * @param ResponseInterface $response A PSR-7 response object
     * @param string|int        $time     A UNIX timestamp or a string compatible with `strtotime()`.
     *
     * @return ResponseInterface A new response object with the `Expires` header set if available
     */
    public function withExpires( ResponseInterface $response, string|int $time ): ResponseInterface
    {
        return $this->httpCache?->withExpires( $response , $time ) ?? $response ;
    }

    /**
     * Add a `Last-Modified` header to a PSR-7 response object.
     *
     * This header informs caches of the last modification date of the resource.
     * If the HTTP cache provider is **not** initialized, the response is returned unchanged.
     *
     * @param ResponseInterface $response A PSR-7 response object
     * @param int|string        $time     A UNIX timestamp or a string compatible with `strtotime()`
     *
     * @return ResponseInterface A new response object with the `Last-Modified` header set if available
     */
    public function withLastModified( ResponseInterface $response , int|string $time ) : ResponseInterface
    {
        return $this->httpCache?->withLastModified( $response , $time ) ?? $response;
    }
}