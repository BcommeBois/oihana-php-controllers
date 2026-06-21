<?php

namespace oihana\controllers\traits;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use Slim\Csrf\Guard;

/**
 * Trait exposing the CSRF token to controllers and templates.
 *
 * The `Slim\Csrf\Guard` instance is provided by dependency injection (an init array or a
 * PSR-11 container), like {@see HttpCacheTrait}/{@see FileEncryptionTrait} — it is never
 * instantiated here. When the guard is not configured every accessor degrades gracefully
 * (`null`, `[]` or `false`) instead of failing.
 *
 * **Token availability:** `Guard::getTokenName()`/`getTokenValue()` only return a value once
 * the guard middleware (or {@see self::generateCsrfToken()}) has populated the key pair for the
 * current request. The field-name keys ({@see self::csrfTokenNameKey()}/{@see self::csrfTokenValueKey()})
 * are always available.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait CsrfTrait
{
    /**
     * The init key holding the DI-provided `Slim\Csrf\Guard` instance.
     */
    public const string CSRF = 'csrf' ;

    /**
     * The CSRF guard reference (optional).
     *
     * @var Guard|null
     */
    protected ?Guard $csrf = null ;

    /**
     * Returns the current CSRF token **name**, or `null` when no token has been generated
     * (or the guard is not configured).
     *
     * @return string|null The current token name, or `null` when unavailable.
     */
    public function csrfTokenName() : ?string
    {
        return $this->csrf?->getTokenName() ;
    }

    /**
     * Returns the field name under which the CSRF token **name** is submitted
     * (e.g. `csrf_name`), or `null` when the guard is not configured.
     *
     * @return string|null The token-name field key, or `null` when the guard is not configured.
     */
    public function csrfTokenNameKey() : ?string
    {
        return $this->csrf?->getTokenNameKey() ;
    }

    /**
     * Returns the current CSRF token **value**, or `null` when no token has been generated
     * (or the guard is not configured).
     *
     * @return string|null The current token value, or `null` when unavailable.
     */
    public function csrfTokenValue() : ?string
    {
        return $this->csrf?->getTokenValue() ;
    }

    /**
     * Returns the field name under which the CSRF token **value** is submitted
     * (e.g. `csrf_value`), or `null` when the guard is not configured.
     *
     * @return string|null The token-value field key, or `null` when the guard is not configured.
     */
    public function csrfTokenValueKey() : ?string
    {
        return $this->csrf?->getTokenValueKey() ;
    }

    /**
     * Returns the current CSRF token as a `[ nameKey => name, valueKey => value ]` map, ready
     * to be injected into a template or a form.
     *
     * @return array<string,string> The token pair, or an empty array when the guard is not
     *                              configured or no token has been generated yet.
     */
    public function csrfTokens() : array
    {
        $name  = $this->csrf?->getTokenName() ;
        $value = $this->csrf?->getTokenValue() ;

        if ( $name === null || $value === null )
        {
            return [] ;
        }

        return
        [
            $this->csrf->getTokenNameKey()  => $name ,
            $this->csrf->getTokenValueKey() => $value ,
        ] ;
    }

    /**
     * Generates a fresh CSRF token pair and stores it.
     *
     * Useful for a controller rendering a form when the guard middleware is not in the request
     * pipeline. Returns the same `[ nameKey => name, valueKey => value ]` shape as {@see self::csrfTokens()}.
     *
     * @return array<string,string> The generated token pair, or an empty array when the guard is not configured.
     *
     * @throws Exception If the guard fails to generate a cryptographically secure token.
     *
     * @example
     * ```php
     * $tokens = $this->generateCsrfToken() ;
     * // [ 'csrf_name' => '...' , 'csrf_value' => '...' ]
     * ```
     */
    public function generateCsrfToken() : array
    {
        return $this->csrf?->generateToken() ?? [] ;
    }

    /**
     * Initialize the internal CSRF guard.
     *
     * Priority order:
     * 1. `$init[self::CSRF]`
     * 2. `$container->get(Guard::class)` when available in DI
     *
     * @param array $init Optional initialization array.
     * @param ContainerInterface|null $container Optional DI container to retrieve the guard.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws NotFoundExceptionInterface If no entry was found in the container for the given identifier.
     */
    public function initializeCsrf( array $init = [] , ?ContainerInterface $container = null ) : static
    {
        $guard = $init[ self::CSRF ] ?? null ;

        if ( !$guard instanceof Guard && $container instanceof ContainerInterface && $container->has( Guard::class ) )
        {
            $guard = $container->get( Guard::class ) ;
        }

        if ( $guard instanceof Guard )
        {
            $this->csrf = $guard ;
        }

        return $this ;
    }

    /**
     * Validates a CSRF token pair against the value stored by the guard.
     *
     * @param string $name  The submitted CSRF token name.
     * @param string $value The submitted CSRF token value.
     *
     * @return bool `true` when the pair is valid; `false` when it is invalid or the guard is not configured.
     */
    public function validateCsrf( string $name , string $value ) : bool
    {
        return $this->csrf?->validateToken( $name , $value ) ?? false ;
    }
}
