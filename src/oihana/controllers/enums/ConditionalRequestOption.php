<?php

namespace oihana\controllers\enums ;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of the option keys accepted by {@see ConditionalRequestTrait::conditionalFileResponse()}.
 *
 * These keys drive how the validating `ETag` is built.
 *
 * @package oihana\controllers\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ConditionalRequestOption
{
    use ConstantsTrait ;

    /**
     * Whether the `ETag` is derived from the file content (`md5_file()`) instead of
     * its metadata (`mtime`-`size`). Content hashing reads the whole file. Default: false.
     */
    public const string HASH_CONTENT = 'hashContent' ;

    /**
     * Whether the `ETag` is emitted as a weak validator (`W/"..."`). Default: false (strong).
     */
    public const string WEAK = 'weak' ;
}
