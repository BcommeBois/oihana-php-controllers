<?php

namespace oihana\controllers\helpers ;

/**
 * Filter an array or object of translations according to the given or available languages.
 *
 * This helper transforms an input array/object from the client to prepare a multilingual (i18n) property.
 * It keeps only string or null values, allows optional transformation or sanitization via a callback.
 *
 * Note: this helper is permissive on input shape — invalid inputs (string, scalar, etc.) silently return null
 * rather than throwing. Callers that need to reject invalid shapes (e.g. to return a 422) must validate the
 * raw input upstream before calling this helper.
 *
 * @param mixed              $fields    Input translations (array<string,string|null> or object). Any other shape (string, scalar, …) is treated as invalid and ignored — the function returns null. Type validation must be done upstream by callers.
 * @param array<string>|null $languages Optional array of languages to filter the i18n definitions. If null, no filtering is applied.
 * @param callable|null      $sanitize  Optional callback to transform or sanitize each value.  Signature: `fn(string|null $value, string $lang): string|null`
 *
 * @return array<string, string|null>|null Filtered translations matching the languages, or null if input is empty.
 *
 * @example
 * ```php
 * $translations =
 * [
 *     'fr' => 'Bonjour <span style="color:red">monde</span>',
 *     'en' => 'Hello <span style="color:red">world</span>',
 *     'de' => 42, // ignored because not string/null
 *     'es' => null
 * ];
 *
 * // Basic filtering for 'fr' and 'en'
 * $filtered = filterLanguages($translations, ['fr', 'en']);
 * // [
 * //     'fr' => 'Bonjour <span style="color:red">monde</span>',
 * //     'en' => 'Hello <span style="color:red">world</span>'
 * // ]
 *
 * // Filtering with HTML sanitization
 * $sanitized = filterLanguages($translations, ['fr', 'en'], function($value, $lang) {
 * if (is_string($value)) {
 * return preg_replace('/(<[^>]+) style=".*?"/i', '$1', $value);
 * }
 * return $value;
 * });
 * // [
 * //     'fr' => 'Bonjour <span>monde</span>',
 * //     'en' => 'Hello <span>world</span>'
 * // ]
 *
 * // Filtering with custom transformation: uppercase strings
 * $upper = filterLanguages($translations, ['fr', 'en'], fn($v, $lang) => is_string($v) ? strtoupper($v) : $v);
 * // [
 * //     'fr' => 'BONJOUR <SPAN STYLE="COLOR:RED">MONDE</SPAN>',
 * //     'en' => 'HELLO <SPAN STYLE="COLOR:RED">WORLD</SPAN>'
 * // ]
 * ```
 *
 * @package oihana\controllers\helpers
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
function filterLanguages
(
    mixed     $fields ,
    ?array    $languages = null ,
    ?callable $sanitize  = null ,
)
:?array
{
    if( is_object( $fields ) )
    {
        $fields = (array) $fields ;
    }

    if ( !is_array( $fields ) || empty( $fields ) )
    {
        return null ;
    }

    $items = [] ;

    foreach ( $languages as $lang )
    {
        $value = $fields[ $lang ] ?? null ;

        if ( !is_string( $value ) && !is_null( $value ) )
        {
            continue ;
        }

        if ( $sanitize !== null )
        {
            $value = $sanitize( $value , $lang ) ;
        }

        $items[ $lang ] = $value ;
    }

    return empty( $items ) ? null : $items ;
}