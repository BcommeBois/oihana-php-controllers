<?php

namespace oihana\controllers\traits\prepare;

use oihana\controllers\traits\RedirectsTrait;

/**
 * Resolves a route argument through the controller redirection table.
 *
 * This trait maps an incoming identifier to its canonical replacement when a redirection is
 * declared for it. It looks up the `$redirects[$redirectID]` table (provided by
 * {@see RedirectsTrait}) and returns the mapped value when the identifier has an entry;
 * otherwise the original identifier is returned unchanged.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareOrRedirectArgumentTrait
{
    use RedirectsTrait ;

    /**
     * Prepares an argument and redirects it if possible.
     * @param string|null $id         The incoming identifier to resolve against the redirection table.
     * @param string|null $redirectID The key selecting which redirection table to use, or null to skip redirection.
     * @return mixed The redirected value when a mapping exists, otherwise the original `$id`.
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