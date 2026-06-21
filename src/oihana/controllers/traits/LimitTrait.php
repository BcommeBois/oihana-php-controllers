<?php

namespace oihana\controllers\traits ;

use xyz\oihana\schema\Pagination;

/**
 * The limit/offset trait.
 */
trait LimitTrait
{
    /**
     * The default limit value.
     * @var int|null
     */
    public ?int $limit = null ;

    /**
     * The maximum limit value.
     * @var int|null
     */
    public ?int $maxLimit = null ;

    /**
     * The minimum limit value.
     * @var int|null
     */
    public ?int $minLimit = null ;

    /**
     * The default limit value.
     * @var int|null
     */
    public ?int $offset = null ;

    /**
     * Initialize the min/max limit range.
     * @param array $init
     * @return static
     */
    public function initializeLimit( array $init = [] ):static
    {
        $this->limit    = $init[ Pagination::LIMIT     ] ?? $this->limit    ;
        $this->maxLimit = $init[ Pagination::MAX_LIMIT ] ?? $this->maxLimit ;
        $this->minLimit = $init[ Pagination::MIN_LIMIT ] ?? $this->minLimit ;
        $this->offset   = $init[ Pagination::OFFSET    ] ?? $this->offset   ;
        return $this ;
    }
}