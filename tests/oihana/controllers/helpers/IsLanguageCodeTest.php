<?php

namespace tests\oihana\controllers\helpers;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use stdClass;

use function oihana\controllers\helpers\isLanguageCode;

/**
 * Test suite for the isLanguageCode() helper.
 *
 * A language tag names a key of a translations map, so it is written verbatim
 * rather than passed as a parameter — this helper is what makes that
 * interpolation safe, and the refusal cases below are the reason it exists.
 */
#[CoversFunction('oihana\controllers\helpers\isLanguageCode')]
final class IsLanguageCodeTest extends TestCase
{
    public static function provideValidCodes() : array
    {
        return
        [
            'two letters'       => [ 'fr'         ] ,
            'three letters'     => [ 'ast'        ] ,
            'region'            => [ 'pt-BR'      ] ,
            'script and region' => [ 'zh-Hant-TW' ] ,
            'numeric region'    => [ 'es-419'     ] ,
        ] ;
    }

    public static function provideInvalidCodes() : array
    {
        return
        [
            'empty'         => [ ''            ] ,
            'single letter' => [ 'f'           ] ,
            'four letters'  => [ 'fran'        ] ,
            'uppercase'     => [ 'FR'          ] ,
            'space'         => [ 'fr fr'       ] ,
            'quote'         => [ 'fr"'         ] ,
            'injection'     => [ 'fr" || 1==1' ] ,
            'dot path'      => [ 'fr.name'     ] ,
            'trailing dash' => [ 'fr-'         ] ,
            'leading dash'  => [ '-fr'         ] ,
            'underscore'    => [ 'fr_FR'       ] ,
            'digits'        => [ '42'          ] ,
        ] ;
    }

    public static function provideInvalidTypes() : array
    {
        return
        [
            'null'   => [ null           ] ,
            'int'    => [ 42             ] ,
            'float'  => [ 1.5            ] ,
            'bool'   => [ true           ] ,
            'array'  => [ [ 'fr' ]       ] ,
            'object' => [ new stdClass() ] ,
        ] ;
    }

    #[Test]
    #[DataProvider('provideValidCodes')]
    public function isLanguageCodeAcceptsValidTags( string $value ) : void
    {
        $this->assertTrue( isLanguageCode( $value ) ) ;
    }

    #[Test]
    #[DataProvider('provideInvalidCodes')]
    public function isLanguageCodeRejectsUnsafeTags( string $value ) : void
    {
        $this->assertFalse( isLanguageCode( $value ) ) ;
    }

    #[Test]
    #[DataProvider('provideInvalidTypes')]
    public function isLanguageCodeRejectsNonStrings( mixed $value ) : void
    {
        $this->assertFalse( isLanguageCode( $value ) ) ;
    }
}
