<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;
use function oihana\files\path\joinPaths;

/**
 * Provides path-related properties and helpers for controllers exposing resource routes.
 *
 * This trait manages the controller's own `path`, its absolute `fullPath`, and an optional
 * `ownerPath` used to build nested, owner-scoped resource URLs.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PathTrait
{
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
     * Returns the full owner path URL for a specific owner identifier.
     *
     * The result joins the `ownerPath`, the given owner `$id` and the controller `path`
     * (e.g. `owners/42/articles`).
     *
     * @param string $id The owner identifier to inject between the owner path and the resource path.
     *
     * @return string The joined owner-scoped resource path.
     */
    public function getFullOwnerPath( string $id ):string
    {
        return joinPaths( $this->ownerPath , $id , $this->path ) ; // TODO use a format strategy 'foo/:id1/bar/:id2/zoo/:id3' ...
    }

    /**
     * Sets the path properties of the controller.
     *
     * Reads the `path`, `fullPath` and `ownerPath` values from the corresponding
     * `ControllerParam` keys of the initialization array, falling back to sensible
     * defaults when they are not provided.
     *
     * @param array $init Optional initialization array.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializePath( array $init = [] ) :static
    {
        $this->path      = $init[ ControllerParam::PATH       ] ?? Char::EMPTY ;
        $this->fullPath  = $init[ ControllerParam::FULL_PATH  ] ?? ( Char::SLASH . $this->path ) ;
        $this->ownerPath = $init[ ControllerParam::OWNER_PATH ] ?? Char::EMPTY ;
        return $this ;
    }
}