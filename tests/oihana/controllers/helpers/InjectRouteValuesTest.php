<?php

namespace tests\oihana\controllers\helpers ;

use PHPUnit\Framework\TestCase;

use Slim\Psr7\Factory\ServerRequestFactory;

use Psr\Http\Message\ServerRequestInterface as Request;

use function oihana\controllers\helpers\injectRouteValues;

final class InjectRouteValuesTest extends TestCase
{
    /**
     * A POST request carrying the given parsed body.
     *
     * @param array<string,mixed> $body
     */
    private function request( array $body = [] ) :Request
    {
        $request = new ServerRequestFactory()->createServerRequest( 'POST' , '/owners/7/items' ) ;

        return $body === [] ? $request : $request->withParsedBody( $body ) ;
    }

    public function testReturnsNullWhenRequestIsNull() :void
    {
        $this->assertNull( injectRouteValues( null , [ 'ownerId' => '7' ] , [ 'ownerId' => 'owner' ] ) ) ;
    }

    public function testInjectsTheRouteValueIntoTheBody() :void
    {
        $request = injectRouteValues( $this->request([ 'name' => 'Chair' ]) , [ 'ownerId' => '7' ] , [ 'ownerId' => 'owner' ] ) ;

        $this->assertSame( [ 'name' => 'Chair' , 'owner' => '7' ] , $request?->getParsedBody() ) ;
    }

    public function testTheRouteWinsOverTheSuppliedValue() :void
    {
        $request = injectRouteValues( $this->request([ 'owner' => '99' ]) , [ 'ownerId' => '7' ] , [ 'ownerId' => 'owner' ] ) ;

        $this->assertSame( [ 'owner' => '7' ] , $request?->getParsedBody() ) ;
    }

    public function testInjectsIntoAnEmptyBody() :void
    {
        $request = injectRouteValues( $this->request() , [ 'ownerId' => '7' ] , [ 'ownerId' => 'owner' ] ) ;

        $this->assertSame( [ 'owner' => '7' ] , $request?->getParsedBody() ) ;
    }

    public function testInjectsSeveralBindingsAtOnce() :void
    {
        $request = injectRouteValues
        (
            $this->request() ,
            [ 'ownerId' => '7' , 'itemId' => 12 ] ,
            [ 'ownerId' => 'owner' , 'itemId' => 'item' ]
        ) ;

        // An int placeholder is cast to string, as it reads in the URL.
        $this->assertSame( [ 'owner' => '7' , 'item' => '12' ] , $request?->getParsedBody() ) ;
    }

    public function testADottedFieldWinsOverTheNestedSuppliedValue() :void
    {
        $request = injectRouteValues
        (
            $this->request([ 'owner' => [ 'id' => '99' , 'name' => 'Alice' ] ]) ,
            [ 'ownerId' => '7' ] ,
            [ 'ownerId' => 'owner.id' ]
        ) ;

        // Only the designated leaf is rewritten — the sibling keys are left alone.
        $this->assertSame( [ 'owner' => [ 'id' => '7' , 'name' => 'Alice' ] ] , $request?->getParsedBody() ) ;
    }

    public function testADottedFieldCreatesTheMissingLevels() :void
    {
        $request = injectRouteValues( $this->request([ 'name' => 'Chair' ]) , [ 'ownerId' => '7' ] , [ 'ownerId' => 'owner.id' ] ) ;

        $this->assertSame( [ 'name' => 'Chair' , 'owner' => [ 'id' => '7' ] ] , $request?->getParsedBody() ) ;
    }

    public function testAnEmptyFieldLeavesTheRequestUntouched() :void
    {
        $original = $this->request([ 'name' => 'Chair' ]) ;

        $this->assertSame( $original , injectRouteValues( $original , [ 'ownerId' => '7' ] , [ 'ownerId' => '' ] ) ) ;
    }

    public function testAnObjectBodyIsNormalizedToAnArray() :void
    {
        $request = injectRouteValues( $this->request()->withParsedBody( (object) [ 'name' => 'Chair' ] ) , [ 'ownerId' => '7' ] , [ 'ownerId' => 'owner' ] ) ;

        // The content is carried over, but the rewritten body is always an associative array.
        $this->assertSame( [ 'name' => 'Chair' , 'owner' => '7' ] , $request?->getParsedBody() ) ;
    }

    public function testAMissingOrEmptyPlaceholderLeavesTheRequestUntouched() :void
    {
        $original = $this->request([ 'name' => 'Chair' ]) ;

        // Absent, empty, and non-scalar placeholders are all skipped.
        $this->assertSame( $original , injectRouteValues( $original , [] , [ 'ownerId' => 'owner' ] ) ) ;
        $this->assertSame( $original , injectRouteValues( $original , [ 'ownerId' => '' ] , [ 'ownerId' => 'owner' ] ) ) ;
        $this->assertSame( $original , injectRouteValues( $original , [ 'ownerId' => [ '7' ] ] , [ 'ownerId' => 'owner' ] ) ) ;
    }

    public function testNoBindingLeavesTheRequestUntouched() :void
    {
        $original = $this->request([ 'name' => 'Chair' ]) ;

        $this->assertSame( $original , injectRouteValues( $original , [ 'ownerId' => '7' ] , [] ) ) ;
    }
}