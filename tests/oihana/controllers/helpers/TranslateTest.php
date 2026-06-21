<?php

namespace tests\oihana\controllers\helpers ;

use PHPUnit\Framework\TestCase;

use function oihana\controllers\helpers\translate;

final class TranslateTest extends TestCase
{
    public function testTranslateWithArrayExistingLang(): void
    {
        $translations = [
            'fr' => 'Bonjour',
            'en' => 'Hello',
            'es' => 'Hola'
        ];

        $this->assertSame('Hello', translate($translations, 'en'));
        $this->assertSame('Bonjour', translate($translations, 'fr'));
    }

    public function testTranslateWithArrayFallback(): void
    {
        $translations = [
            'fr' => 'Bonjour',
            'en' => 'Hello',
        ];

        // Requested language missing, fallback exists
        $this->assertSame('Bonjour', translate($translations, 'de', 'fr'));
    }

    public function testTranslateWithArrayNoMatch(): void
    {
        $translations = [
            'fr' => 'Bonjour',
            'en' => 'Hello',
        ];

        // Requested language and fallback missing
        $this->assertNull(translate($translations, 'de', 'es'));
    }

    public function testTranslateReturnsAllWhenLangIsNull(): void
    {
        $translations = [
            'fr' => 'Bonjour',
            'en' => 'Hello',
        ];

        $this->assertSame($translations, translate($translations));
    }

    public function testTranslateWithObject(): void
    {
        $translations = (object)[
            'fr' => 'Bonjour',
            'en' => 'Hello',
        ];

        $this->assertSame('Bonjour', translate($translations, 'fr'));
        $this->assertSame('Hello', translate($translations, 'en'));
        $this->assertSame( $translations , translate( $translations , null));
    }

    public function testTranslateWithEmptyArray(): void
    {
        $this->assertNull(translate([], 'fr'));
        $this->assertSame([], translate([], null));
    }

    public function testTranslateWithNullFields(): void
    {
        $this->assertNull(translate(null, 'fr'));
        $this->assertNull(translate(null, null));
    }

    public function testTranslateFallbackIsNull(): void
    {
        $translations = ['fr' => 'Bonjour'];

        // fallback is null, requested language missing
        $this->assertNull(translate($translations, 'en', null));
    }

    public function testTranslateRequestedLangExistsOverridesFallback(): void
    {
        $translations = ['fr' => 'Bonjour', 'en' => 'Hello'];

        $this->assertSame('Hello', translate($translations, 'en', 'fr'));
    }
}