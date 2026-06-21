<?php

namespace oihana\controllers\traits;

use oihana\enums\Char;

trait SortAfterTrait
{
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