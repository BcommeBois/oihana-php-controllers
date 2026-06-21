<?php

namespace oihana\controllers\traits ;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use oihana\controllers\enums\ControllerParam;

/**
 * Trait LanguagesTrait
 *
 * Provides a helper method to initialize and store the list of supported languages
 * for a controller.
 *
 * This trait manages:
 *   - The `$languages` property containing valid language codes.
 *   - The initialization of `$languages` from an array or a PSR-11 container.
 *
 * Example usage:
 * ```php
 * $this->initializeLanguages(['languages' => ['fr', 'en']]);
 *
 * // Or with a PSR-11 container
 * $this->initializeLanguages([], $container);
 *
 * // Access the languages
 * echo $this->languages; // ['fr', 'en']
 * ```
 *
 * @property array<string> $languages List of supported language codes.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait LanguagesTrait
{
    /**
     * Array of valid language codes supported by the controller.
     *
     * @var array<string>
     */
    public array $languages = [] ;

    /**
     * Key used to initialize languages in an array or container.
     */
    public const string LANGUAGES = 'languages' ;

    /**
     * Initialize the internal `$languages` property from an array or a PSR-11 container.
     *
     * This method first checks the provided `$init` array for a key 'languages'.
     * If not found, it optionally checks a PSR-11 container for fallback configuration.
     *
     * @param array                   $init      Optional array with key 'languages' containing supported language codes.
     * @param ContainerInterface|null $container Optional PSR-11 container for fallback configuration.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws ContainerExceptionInterface If the container fails to retrieve the languages.
     * @throws NotFoundExceptionInterface  If the requested languages key is not found in the container.
     */
    public function initializeLanguages( array $init = [] , ?ContainerInterface $container = null ) :static
    {
        $languages = $init[ ControllerParam::LANGUAGES ] ?? null ;

        if( $languages == null && $container?->has( self::LANGUAGES ) )
        {
            $languages = $container->get( ControllerParam::LANGUAGES ) ;
        }

        $this->languages = is_array( $languages ) ? $languages : [] ;

        return $this ;
    }
}