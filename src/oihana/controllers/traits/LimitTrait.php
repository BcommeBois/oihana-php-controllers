<?php

namespace oihana\controllers\traits ;

use xyz\oihana\schema\Pagination;

/**
 * Holds the limit/offset pagination bounds of a controller.
 *
 * This trait exposes the default `limit` and `offset` values together with the
 * `minLimit`/`maxLimit` range used to clamp the page size requested by a client.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
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
     * The default offset value.
     * @var int|null
     */
    public ?int $offset = null ;

    /**
     * Initializes the limit, offset and min/max limit range from an array.
     *
     * @param array $init Initialization array, read from the {@see Pagination} keys (LIMIT, MAX_LIMIT, MIN_LIMIT, OFFSET).
     *
     * @return static Returns the current instance for method chaining.
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