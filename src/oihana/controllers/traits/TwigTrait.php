<?php

namespace oihana\controllers\traits ;

use InvalidArgumentException;
use oihana\enums\http\HttpHeader;
use oihana\enums\http\MediaType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Views\Twig;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Provides seamless integration of the **Twig** templating engine into controllers using the **Slim Framework**.
 *
 * This trait offers:
 * - Automatic initialization of a `Twig` instance from a provided configuration array or via a **PSR-11** dependency injection container.
 * - A simplified `render()` method for rendering Twig templates into a PSR-7 `ResponseInterface`.
 * - Error handling through Twig's native exceptions for better debugging.
 *
 * Typical usage within a Slim controller:
 *
 * ```php
 * use oihana\controllers\traits\TwigTrait;
 *
 * class MyController {
 *     use TwigTrait;
 *
 *     public function __construct(ContainerInterface $container) {
 *         $this->initializeTwig([], $container);
 *     }
 *
 *     public function home($request, $response) {
 *         return $this->render($response, 'home.twig', [
 *             'title' => 'Welcome!',
 *             'user'  => 'Marc'
 *         ]);
 *     }
 * }
 * ```
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 * @see     https://www.slimframework.com/docs/v4/features/templates.html
 * @see     https://twig.symfony.com/
 */
trait TwigTrait
{
    /**
     * The Twig view renderer instance.
     * @var Twig
     */
    public Twig $twig ;

    /**
     * The container key used to retrieve the Twig instance.
     * @var string
     */
    public const string TWIG = 'twig' ;

    /**
     * Initializes the Twig environment for rendering templates.
     *
     * This method first checks if a Twig instance is provided in the `$init` array.
     * If not, and a PSR-11 container is available, it attempts to fetch the Twig instance using the `self::TWIG` key.
     *
     * @param array                   $init      Optional initialization array (e.g., `['twig' => Twig $instance]`).
     * @param ContainerInterface|null $container Optional PSR-11 container for retrieving the Twig instance.
     *
     * @return static Returns the current instance for method chaining.
     *
     * @throws NotFoundExceptionInterface  If no entry was found in the container for the given identifier.
     * @throws ContainerExceptionInterface If the container encounters an error while retrieving an entry.
     * @throws InvalidArgumentException    If no valid Twig instance is provided or available.
     */
    public function initializeTwig
    (
        array               $init      = [] ,
        ?ContainerInterface $container = null ,
    )
    :static
    {
        $twig = $init[ self::TWIG ] ?? null ;

        if( !$twig instanceof Twig && $container instanceof ContainerInterface && $container->has( self::TWIG ) )
        {
            $twig = $container->get( self::TWIG ) ;
        }

        if( !$twig instanceof Twig && $container instanceof ContainerInterface && $container->has( Twig::class ) )
        {
            $twig = $container->get( Twig::class ) ;
        }

        if ( !$twig instanceof Twig )
        {
            throw new InvalidArgumentException
            (
                sprintf
                (
                    "Invalid Twig instance. Expected an instance of '%s', got '%s'.",
                    Twig::class , is_object( $twig ) ? get_class( $twig ) : gettype( $twig )
                )
            );
        }

        $this->twig = $twig ;

        return $this ;
    }

    /**
     * Renders a Twig template into a PSR-7 response.
     *
     * @param ?Response $response The PSR-7 response instance.
     * @param string    $template The name of the Twig template to render.
     * @param array     $args     Optional array of parameters passed to the template.
     *
     * @return ?Response The modified response instance, or `null` if `$response` is not provided.
     *
     * @throws LoaderError  If the template cannot be found.
     * @throws RuntimeError If an error occurs during template rendering.
     * @throws SyntaxError  If the Twig template contains a syntax error.
     */
    public function render( ?Response $response , string $template , array $args = [] ) : ?Response
    {
        if ( !isset( $response ) )
        {
            return null ;
        }

        $response = $this->twig->render( $response , $template , $args ) ;

        // Default to text/html when the caller did not declare a content type, so
        // the response is nosniff-compatible and downstream middlewares can detect
        // HTML. Renders that produce XML / SVG / text set their own type and keep it.
        if ( !$response->hasHeader( HttpHeader::CONTENT_TYPE ) )
        {
            $response = $response->withHeader
            (
                HttpHeader::CONTENT_TYPE ,
                MediaType::withCharset( MediaType::HTML )
            ) ;
        }

        return $response ;
    }
}