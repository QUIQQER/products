<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\TextareaMultiLang;
use QUI\ERP\Products\Handler\Search;

class TextareaMultiLangTest extends TestCase
{
    public function testLocalizedValueAndEmptyStateReflectStoredTranslations(): void
    {
        $Field = new TextareaMultiLang(9101);
        $Locale = new QUI\Locale();
        $Locale->setCurrent('de');

        self::assertTrue($Field->isEmpty());
        $Field->setValue(['de' => 'Deutscher Text', 'en' => 'English text']);
        self::assertSame('Deutscher Text', $Field->getValueByLocale($Locale));
        self::assertFalse($Field->isEmpty());

        $Locale->setCurrent('fr');
        self::assertSame('', $Field->getValueByLocale($Locale));
        $Field->setValue(['de' => '', 'en' => '']);
        self::assertTrue($Field->isEmpty());
    }

    public function testCleanupKeepsLanguageKeysAndDropsMalformedKeys(): void
    {
        $Field = new TextareaMultiLang(9101);
        $cleaned = $Field->cleanup([
            'de' => 'Deutsch',
            'en' => 'English',
            'invalid' => 'ignored',
            5 => 'ignored'
        ]);

        self::assertSame('Deutsch', $cleaned['de']);
        self::assertSame('English', $cleaned['en']);
        self::assertArrayNotHasKey('invalid', $cleaned);
        self::assertArrayNotHasKey(5, $cleaned);
        self::assertNull($Field->cleanup(''));
    }

    public function testCleanupAcceptsJsonAndNeutralizesInvalidInput(): void
    {
        $Field = new TextareaMultiLang(9101);

        $json = $Field->cleanup('{"de":"JSON-Text"}');
        self::assertSame('JSON-Text', $json['de']);

        $invalidJson = $Field->cleanup('{invalid json');
        self::assertNotNull($invalidJson);
        self::assertNotContains('{invalid json', $invalidJson, true);

        $invalidType = $Field->cleanup(new \stdClass());
        self::assertNotNull($invalidType);
        self::assertNotContains(new \stdClass(), $invalidType, true);
    }

    #[DataProvider('invalidTranslations')]
    public function testValidationRejectsMalformedTranslations(mixed $value): void
    {
        $Field = new TextareaMultiLang(9101);

        if ($value instanceof \stdClass) {
            json_decode('{}');
        }

        $this->expectException(Exception::class);
        $Field->validate($value);
    }

    public static function invalidTranslations(): iterable
    {
        yield 'invalid json' => ['{invalid json'];
        yield 'long language key' => [['de_DE' => 'Text']];
        yield 'numeric language key' => [[1 => 'Text']];
        yield 'non collection value' => [new \stdClass()];
    }

    public function testViewsAndSearchMetadataDescribeTextareaBehavior(): void
    {
        $Field = new TextareaMultiLang(9101, [
            'title' => ['de' => 'Beschreibung'],
            'value' => ['de' => 'Inhalt']
        ]);

        self::assertSame(9101, $Field->getBackendView()->getId());
        self::assertSame(9101, $Field->getFrontendView()->getId());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/TextareaMultiLang',
            $Field->getJavaScriptControl()
        );
        self::assertSame(Search::SEARCHTYPE_TEXT, $Field->getDefaultSearchType());
        self::assertSame([
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE
        ], $Field->getSearchTypes());
    }
}
