<?php

namespace oihana\controllers\helpers;

/**
 * Checks whether a value is a language tag safe to use as an identifier.
 *
 * A language tag rarely stays a value: it names a key of a translations map
 * (`alternateName.fr`), a directory, a template, or an attribute of a stored
 * document — places where it is written **verbatim** rather than passed as a
 * parameter. Anything interpolating a tag has to prove it harmless first, and
 * this helper is that proof.
 *
 * A valid tag is a two or three letter primary subtag, optionally followed by
 * dash-separated subtags (region, script, variant): `fr`, `en`, `pt-BR`,
 * `zh-Hant-TW`. The primary subtag is lowercase — callers normalise before
 * asking, so `FR` is rejected rather than silently accepted under two spellings.
 *
 * ⚠ A tag carrying a dash is valid here but is **not** always reachable through
 * dot notation (`alternateName.pt-BR` reads as a subtraction in several query
 * languages). The caller emits the bracket form for it; this helper only says
 * the tag is safe.
 *
 * @example
 * ```php
 * use function oihana\controllers\helpers\isLanguageCode;
 *
 * isLanguageCode( 'fr' );         // true
 * isLanguageCode( 'pt-BR' );      // true
 * isLanguageCode( 'zh-Hant-TW' ); // true
 * isLanguageCode( 'FR' );         // false (normalise first)
 * isLanguageCode( 'f' );          // false (too short)
 * isLanguageCode( 'fr"' );        // false
 * isLanguageCode( 'fr fr' );      // false
 * isLanguageCode( '' );           // false
 * isLanguageCode( 42 );           // false (not a string)
 * ```
 *
 * @param mixed $value The value to check.
 *
 * @return bool True when `$value` is a safe language tag.
 *
 * @package oihana\controllers\helpers
 * @since   1.3.0
 * @author  Marc Alcaraz
 */
function isLanguageCode( mixed $value ): bool
{
    if ( !is_string( $value ) || $value === '' )
    {
        return false ;
    }

    return (bool) preg_match( '/^[a-z]{2,3}(-[A-Za-z0-9]{1,8})*$/' , $value ) ;
}
