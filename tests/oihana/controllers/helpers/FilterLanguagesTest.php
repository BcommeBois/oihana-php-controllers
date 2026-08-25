<?php

namespace tests\oihana\controllers\helpers ;

use stdClass;

use PHPUnit\Framework\TestCase;

use function oihana\controllers\helpers\filterLanguages;

final class FilterLanguagesTest extends TestCase
{
    public function testFilterWithArray(): void
    {
        $translations = [
            'fr' => 'Bonjour <span style="color:red">monde</span>',
            'en' => 'Hello <span style="color:red">world</span>',
            'de' => 42,
            'es' => null
        ];

        $result = filterLanguages($translations, ['fr', 'en', 'de', 'es']);
        $this->assertSame
        ([
            'fr' => 'Bonjour <span style="color:red">monde</span>',
            'en' => 'Hello <span style="color:red">world</span>',
            'es' => null
        ], $result);
    }

    public function testFilterWithObject(): void
    {
        $translations = new class {
            public string $fr = 'Bonjour';
            public string $en = 'Hello';
            public int    $de = 42;
            public null   $es = null;
        };

        $result = filterLanguages($translations, ['fr', 'en', 'de', 'es']);
        $this->assertSame([
            'fr' => 'Bonjour',
            'en' => 'Hello',
            'es' => null
        ], $result);
    }

    public function testFilterWithCallback(): void
    {
        $translations = [
            'fr' => 'Bonjour <span style="color:red">monde</span>',
            'en' => 'Hello <span style="color:red">world</span>',
            'es' => null
        ];

        $callback = function( $value , $lang )
        {
            if (is_string($value)) {
                return strtoupper($value);
            }
            return $value;
        };

        $result = filterLanguages($translations, ['fr', 'en', 'es'], $callback);

        $this->assertSame([
            'fr' => 'BONJOUR <SPAN STYLE="COLOR:RED">MONDE</SPAN>',
            'en' => 'HELLO <SPAN STYLE="COLOR:RED">WORLD</SPAN>',
            'es' => null
        ], $result);
    }

    public function testFilterWithHtmlSanitization(): void
    {
        $translations = [
            'fr' => 'Bonjour <span style="color:red">monde</span>',
            'en' => 'Hello <span style="color:red">world</span>'
        ];

        $callback = function($value, $lang) {
            if (is_string($value)) {
                return preg_replace('/(<[^>]+) style=".*?"/i', '$1', $value);
            }
            return $value;
        };

        $result = filterLanguages($translations, ['fr', 'en'], $callback);

        $this->assertSame([
            'fr' => 'Bonjour <span>monde</span>',
            'en' => 'Hello <span>world</span>'
        ], $result);
    }

    public function testFilterWithEmptyLanguages(): void
    {
        $translations = ['fr' => 'Bonjour', 'en' => 'Hello'];
        $result = filterLanguages($translations, []);
        $this->assertNull($result);
    }

    public function testFilterWithEmptyFields(): void
    {
        $this->assertNull(filterLanguages(null, ['fr', 'en']));
        $this->assertNull(filterLanguages([], ['fr', 'en']));
    }

    public function testFilterIgnoresInvalidValues(): void
    {
        $translations = [
            'fr' => 'Bonjour',
            'en' => ['Hello'], // invalid
            'es' => new stdClass() // invalid
        ];

        $result = filterLanguages($translations, ['fr', 'en', 'es']);
        $this->assertSame([
            'fr' => 'Bonjour'
        ], $result);
    }

    public function test_filterLanguages_returns_null_on_string_input() : void
    {
        $this->assertNull( filterLanguages( 'flat string' , [ 'fr' , 'en' ] ) ) ;
    }

    public function test_filterLanguages_returns_null_on_int_input() : void
    {
        $this->assertNull( filterLanguages( 42 , [ 'fr' , 'en' ] ) ) ;
    }

    public function test_filterLanguages_returns_null_on_bool_input() : void
    {
        $this->assertNull( filterLanguages( true , [ 'fr' , 'en' ] ) ) ;
    }

    // -------------------------------------------------------------------------
    // A partial map stays partial
    // -------------------------------------------------------------------------

    /**
     * 🚨 The defect this contract exists to prevent.
     *
     * The absent languages used to be filled in with a null, which turned every
     * partial edit into a full replacement : a caller correcting the French
     * label wiped the English one, and read a 200 saying nothing about it. What
     * is not named is not touched — the rule the rest of a partial body already
     * follows.
     */
    public function testAnAbsentLanguageIsNotInvented(): void
    {
        $result = filterLanguages( [ 'fr' => 'Bonjour' ] , [ 'fr' , 'en' ] ) ;

        $this->assertSame( [ 'fr' => 'Bonjour' ] , $result ) ;
        $this->assertArrayNotHasKey( 'en' , $result ) ;
    }

    /**
     * The same rule seen from the other end : naming a language clears it, and
     * says nothing about the others.
     */
    public function testANullClearsOnlyTheLanguageItNames(): void
    {
        $this->assertSame( [ 'fr' => null ] , filterLanguages( [ 'fr' => null ] , [ 'fr' , 'en' ] ) ) ;
    }

    /**
     * Adding a language to a project must not depend on every client knowing
     * about it : a body that ignores the newcomer leaves it alone.
     */
    public function testALanguageTheCallerIgnoresSurvives(): void
    {
        $this->assertSame( [ 'fr' => 'Bonjour' ] , filterLanguages( [ 'fr' => 'Bonjour' ] , [ 'fr' , 'en' , 'es' ] ) ) ;
    }

    // -------------------------------------------------------------------------
    // An empty string says nothing a null does not
    // -------------------------------------------------------------------------

    public function testAnEmptyStringIsNormalisedToNull(): void
    {
        $this->assertSame( [ 'fr' => null ] , filterLanguages( [ 'fr' => '' ] , [ 'fr' , 'en' ] ) ) ;
    }

    /**
     * The normalisation runs after the callback, so a value that sanitizing
     * empties is cleared rather than stored as an empty label.
     */
    public function testAValueEmptiedBySanitizingIsNormalisedToo(): void
    {
        $result = filterLanguages
        (
            [ 'fr' => '   ' ] ,
            [ 'fr' , 'en' ] ,
            fn( ?string $value , string $lang ) :?string => is_string( $value ) ? trim( $value ) : $value
        ) ;

        $this->assertSame( [ 'fr' => null ] , $result ) ;
    }

    // -------------------------------------------------------------------------
    // The language filter itself
    // -------------------------------------------------------------------------

    /**
     * What the signature has always documented, and what used to fail outright :
     * a null language list applies no filtering at all.
     */
    public function testANullLanguageListKeepsEveryLanguageReceived(): void
    {
        $this->assertSame
        (
            [ 'fr' => 'Bonjour' , 'de' => 'Hallo' ] ,
            filterLanguages( [ 'fr' => 'Bonjour' , 'de' => 'Hallo' ] , null )
        ) ;
    }

    /**
     * A body holding no known language holds nothing to write : the caller reads
     * the null as an explicit null, which is why « touch nothing » is expressed
     * by omitting the property, never by an empty map.
     */
    public function testAnUnknownLanguageAloneYieldsNull(): void
    {
        $this->assertNull( filterLanguages( [ 'de' => 'Hallo' ] , [ 'fr' , 'en' ] ) ) ;
    }
}
