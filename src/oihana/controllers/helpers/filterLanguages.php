<?php

namespace oihana\controllers\helpers ;

/**
 * Filter an array or object of translations according to the given or available languages.
 *
 * This helper transforms an input array/object from the client to prepare a multilingual (i18n) property.
 * It keeps only string or null values, allows optional transformation or sanitization via a callback.
 *
 * 🔑 **Only the languages actually received come back.** A translation map is edited like every other
 * field of a partial body : what the caller did not mention is left alone. Filling the absent languages
 * in — with a null, as this helper used to — turned every partial edit into a full replacement : a body
 * carrying `{ "fr": "Bonjour" }` rewrote the English to null, and the caller read a `200` with nothing
 * to warn them. The rule is now the one that governs the rest of a partial write : **absent means
 * untouched**, and it is what lets a language be added to a project without every edit wiping it.
 *
 * 🔑 **An empty string is normalised to null.** A label that exists but says nothing states no more
 * than a missing label, and keeping both shapes means every reader must test for both. The
 * normalisation runs after the sanitize callback, so a value that sanitizing empties follows the same
 * rule. Clearing one language is therefore `{ "fr": null }` or `{ "fr": "" }`, indifferently.
 *
 * ⚠️ An input carrying **no usable language at all** — an empty map, or only unknown languages — still
 * returns `null`, which a payload layer reads as an explicit null : « clear the whole property ». It is
 * unchanged behaviour, and the reason a caller meaning « touch nothing » must omit the property rather
 * than send an empty map.
 *
 * Note: this helper is permissive on input shape — invalid inputs (string, scalar, etc.) silently return null
 * rather than throwing. Callers that need to reject invalid shapes (e.g. to return a 422) must validate the
 * raw input upstream before calling this helper.
 *
 * @param mixed              $fields    Input translations (array<string,string|null> or object). Any other shape (string, scalar, …) is treated as invalid and ignored — the function returns null. Type validation must be done upstream by callers.
 * @param array<string>|null $languages Optional array of allowed languages. A language outside the list is dropped ; `null` applies no filtering and keeps every language received.
 * @param callable|null      $sanitize  Optional callback to transform or sanitize each value.  Signature: `fn(string|null $value, string $lang): string|null`
 *
 * @return array<string, string|null>|null The received translations, filtered and normalised, or null when the input holds no usable language.
 *
 * @example
 * ```php
 * // A partial edit touches the language it names, and only that one.
 * $filtered = filterLanguages( [ 'fr' => 'Bonjour' ] , [ 'fr' , 'en' ] ) ;
 * // [ 'fr' => 'Bonjour' ]                 <- no 'en' key : the English is left alone
 *
 * // Clearing one language, either way round.
 * $cleared = filterLanguages( [ 'fr' => null ] , [ 'fr' , 'en' ] ) ;
 * // [ 'fr' => null ]
 *
 * $cleared = filterLanguages( [ 'fr' => '' ] , [ 'fr' , 'en' ] ) ;
 * // [ 'fr' => null ]                      <- an empty string says nothing a null does not
 *
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

    foreach ( $fields as $lang => $value )
    {
        if ( is_array( $languages ) && !in_array( $lang , $languages , true ) )
        {
            continue ;
        }

        if ( !is_string( $value ) && !is_null( $value ) )
        {
            continue ;
        }

        if ( $sanitize !== null )
        {
            $value = $sanitize( $value , $lang ) ;
        }

        $items[ $lang ] = $value === '' ? null : $value ;
    }

    return empty( $items ) ? null : $items ;
}
