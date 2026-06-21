<?php

namespace oihana\controllers\traits;

use oihana\enums\Char;

/**
 * Sorts a collection on a nested property declared by the model's `sortable.after` definition.
 *
 * When the bound model exposes a `sortable['after']` entry formatted as `"relation.property"`,
 * this trait reorders the given items in ascending order on that nested property
 * (`$item->relation->property`). If no such definition exists, the items are returned unchanged.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait SortAfterTrait
{
    /**
     * Sorts the given items on the nested property defined by the model's `sortable.after` setting.
     *
     * The `after` definition is expected to be a dot-separated `"relation.property"` string; the
     * items are compared with {@see strcmp()} on `$item->relation->property`. When the model has no
     * usable `after` definition, the items are returned as-is.
     *
     * @param mixed $items The collection of items (objects) to sort.
     *
     * @return mixed The sorted collection, or the original collection when no sort is applied.
     */
    public function sortAfter( $items )
    {
        $sortable = $this->model?->sortable ;
        if( $sortable && array_key_exists( 'after' , $sortable ) )
        {
            $after = explode( Char::DOT , $sortable['after'] ) ;
            if( $after && is_array( $after ) && count( $after ) == 2 )
            {
                usort( $items , function ( $a , $b ) use ( $after )
                {
                    return strcmp( $a->{$after[0]}->{$after[1]} , $b->{$after[0]}->{$after[1]} ) ;
                }) ;
            }
        }
        return $items ;
    }
}