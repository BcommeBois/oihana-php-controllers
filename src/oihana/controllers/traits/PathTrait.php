<?php

namespace oihana\controllers\traits ;

use oihana\controllers\enums\ControllerParam;
use oihana\enums\Char;
use function oihana\files\path\joinPaths;

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
     * Returns the full owner path url with a specific owner identifier.
     * @param string $id
     * @return string
     */
    public function getFullOwnerPath( string $id ):string
    {
        return joinPaths( $this->ownerPath , $id , $this->path ) ; // TODO use a format strategy 'foo/:id1/bar/:id2/zoo/:id3' ...
    }

    /**
     * Sets the path of the controller.
     * @param array $init
     * return static
     */
    public function initializePath( array $init = [] ) :static
    {
        $this->path      = $init[ ControllerParam::PATH       ] ?? Char::EMPTY ;
        $this->fullPath  = $init[ ControllerParam::FULL_PATH  ] ?? ( Char::SLASH . $this->path ) ;
        $this->ownerPath = $init[ ControllerParam::OWNER_PATH ] ?? Char::EMPTY ;
        return $this ;
    }
}