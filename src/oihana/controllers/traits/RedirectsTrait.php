<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;

trait RedirectsTrait
{
    /**
     * The redirects settings.
     * @var array
     */
    public array $redirects = [] ;

    /**
     * Initialize the redirects property.
     */
    public function initializeRedirects( array $init = [] ):void
    {
        $this->redirects = $init[ ControllerParam::REDIRECTS ] ?? [] ;
    }
}