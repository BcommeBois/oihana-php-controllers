<?php

namespace tests\oihana\controllers\traits\prepare;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Shared helpers for the controllers\traits\prepare\* trait tests.
 *
 * Not a *Test.php file, so PHPUnit does not collect it as a test case.
 */
abstract class PrepareTestCase extends TestCase
{
    /**
     * Builds a PSR-7 request stub exposing the given query and parsed-body params.
     */
    protected function request( array $query = [] , array $body = [] ): Request
    {
        $request = $this->createStub( Request::class ) ;
        $request->method( 'getQueryParams' )->willReturn( $query ) ;
        $request->method( 'getParsedBody' )->willReturn( $body ) ;
        return $request ;
    }
}
