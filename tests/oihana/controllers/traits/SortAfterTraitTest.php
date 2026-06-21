<?php

namespace tests\oihana\controllers\traits;

use oihana\controllers\traits\SortAfterTrait;

use PHPUnit\Framework\TestCase;

final class SortAfterTraitTest extends TestCase
{
    private object $mock;

    protected function setUp(): void
    {
        $this->mock = new class
        {
            use SortAfterTrait;

            public ?object $model = null ;
        };
    }

    /**
     * Builds items whose `$group->label` nested property drives the sort.
     */
    private function items( array $labels ): array
    {
        return array_map
        (
            fn( $label ) => (object) [ 'group' => (object) [ 'label' => $label ] ] ,
            $labels
        );
    }

    public function testSortsItemsByNestedProperty(): void
    {
        $this->mock->model = (object) [ 'sortable' => [ 'after' => 'group.label' ] ];

        $items  = $this->items( [ 'charlie' , 'alpha' , 'bravo' ] );
        $sorted = $this->mock->sortAfter( $items );

        $this->assertSame( [ 'alpha' , 'bravo' , 'charlie' ] , array_map( fn( $i ) => $i->group->label , $sorted ) );
    }

    public function testReturnsItemsUnchangedWhenNoModel(): void
    {
        $items = $this->items( [ 'b' , 'a' ] );
        $this->assertSame( $items , $this->mock->sortAfter( $items ) );
    }

    public function testReturnsItemsUnchangedWhenSortableHasNoAfterKey(): void
    {
        $this->mock->model = (object) [ 'sortable' => [ 'other' => 'x' ] ];

        $items = $this->items( [ 'b' , 'a' ] );
        $this->assertSame( $items , $this->mock->sortAfter( $items ) );
    }

    public function testReturnsItemsUnchangedWhenAfterIsNotTwoSegments(): void
    {
        $this->mock->model = (object) [ 'sortable' => [ 'after' => 'single' ] ];

        $items = $this->items( [ 'b' , 'a' ] );
        $this->assertSame( $items , $this->mock->sortAfter( $items ) );
    }
}
