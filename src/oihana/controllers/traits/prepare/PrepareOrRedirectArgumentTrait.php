<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\traits\RedirectsTrait;

trait PrepareOrRedirectArgumentTrait
{
    use RedirectsTrait ;

    /**
     * Prepares an argument and redirects it if possible.
     * @param string|null $id
     * @param string|null $redirectID
     * @return mixed
     */
    public function prepareOrRedirectArgument( ?string $id , ?string $redirectID ) :mixed
    {
        if( isset( $id ) && isset( $redirectID ) )
        {
            $redirects = $this->redirects[ $redirectID ] ?? [] ;
            if( isset( $redirects[ $id ] ) )
            {
                return $redirects[ $id ] ;
            }
        }
        return $id ;
    }
}