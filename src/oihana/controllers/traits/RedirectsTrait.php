<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;

/**
 * Provides a `redirects` settings array allowing a controller to define static URL redirections.
 *
 * The map is populated from the controller initialization array and can be consumed by the
 * controller to resolve redirect targets.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait RedirectsTrait
{
    /**
     * The redirects settings.
     * @var array
     */
    public array $redirects = [] ;

    /**
     * Initialize the `redirects` property from the initialization array.
     *
     * Reads the `ControllerParam::REDIRECTS` key, defaulting to an empty array when absent.
     *
     * @param array $init Optional initialization array.
     *
     * @return void
     */
    public function initializeRedirects( array $init = [] ):void
    {
        $this->redirects = $init[ ControllerParam::REDIRECTS ] ?? [] ;
    }
}