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
}