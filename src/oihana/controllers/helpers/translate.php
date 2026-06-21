<?php

namespace oihana\controllers\helpers ;

/**
 * Retrieve the translation for a specific language, with optional fallback to a default language.
 *
 * This version is safer: if the requested language and the fallback are missing, it returns `null`
 * instead of returning the full array/object.
 *
 * Example usage:
 * ```php
 * $translations =
 * [
 *     'fr' => 'Bonjour',
 *     'en' => 'Hello',
 *     'es' => 'Hola'
 * ];
 *
 * translate( $translations , 'en' );        // 'Hello'
 * translate( $translations , 'de' , 'fr' ); // 'Bonjour' (fallback)
 * translate( $translations , 'it' , 'de' ); // null
 * translate( $translations );               // ['fr' => 'Bonjour', 'en' => 'Hello', 'es' => 'Hola']
 *
 * $translationsObj = (object) $translations ;
 * translate( $translationsObj , null ) ; // (object) ['fr' => 'Bonjour', 'en' => 'Hello', 'es' => 'Hola']
 * ```
 *
 * @param array<string, mixed>|object|null $fields  Array or object of translations keyed by language codes.
 * @param string|null                      $lang    Desired language code. If null, returns all translations.
 * @param string|null                      $default Optional fallback language code if `$lang` is missing.
 *
 * @return mixed Returns the translation for the requested language, the fallback, all translations (if `$lang` is null), or `null` if no match.
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function translate
(
    array|object|null $fields ,
    string|null       $lang    = null ,
    string|null       $default = null ,
)
:mixed
{
    if ( $fields === null )
    {
        return null;
    }

    if ( $lang === null )
    {
        return $fields ;
    }

    $fieldsArray = is_object( $fields ) ? (array) $fields : $fields ;

    if ( !is_array( $fieldsArray) || empty( $fieldsArray ) )
    {
        return null ;
    }

    if ( array_key_exists( $lang , $fieldsArray ) )
    {
        return $fieldsArray[ $lang ] ;
    }

    if ( $default !== null && array_key_exists( $default , $fieldsArray ) )
    {
        return $fieldsArray[ $default ] ;
    }

    return null ;
}