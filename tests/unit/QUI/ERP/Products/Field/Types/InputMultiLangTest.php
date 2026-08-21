<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\InputMultiLang;
use QUI\ERP\Products\Handler\Search;

class InputMultiLangTest extends TestCase
{
    public function testLocalizedLookupSupportsExactRegionalAndFallbackValues(): void
    {
        $Field = new InputMultiLang(9102);
        $Locale = new QUI\Locale();
        $Field->setValue([
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français'
        ]);

        $Locale->setCurrent('de');
        self::assertSame('Deutsch', $Field->getValueByLocale($Locale));
        $Locale->setCurrent('de_DE');
        self::assertSame('Deutsch', $Field->getValueByLocale($Locale));
        $Locale->setCurrent('en');
        self::assertSame('English', $Field->getValueByLocale($Locale));
        $Locale->setCurrent('it');
        self::assertSame('Deutsch', $Field->getValueByLocale($Locale));
    }

    public function testSetValueByLocaleUpdatesOnlyTheSelectedTranslation(): void
    {
        $Field = new InputMultiLang(9102);
        $Field->setValue('{"de":"Alt","en":"Old"}');
        $Locale = new QUI\Locale();
        $Locale->setCurrent('en');

        $Field->setValueByLocale('New', $Locale);

        self::assertSame(['de' => 'Alt', 'en' => 'New'], $Field->getValue());
        self::assertSame('New', $Field->getValueByLocale($Locale));
    }

    public function testCleanupCompletesLanguagesAndDropsInvalidKeys(): void
    {
        $Field = new InputMultiLang(9102);
        $cleaned = $Field->cleanup([
            'de' => 'Deutsch',
            'invalid' => 'ignored',
            3 => 'ignored'
        ]);

        self::assertSame('Deutsch', $cleaned['de']);
        self::assertArrayNotHasKey('invalid', $cleaned);
        self::assertArrayNotHasKey(3, $cleaned);
        self::assertNotContains('ignored', $cleaned, true);
        self::assertNotContains('invalid input', $Field->cleanup('invalid input'), true);
    }

    #[DataProvider('invalidTranslations')]
    public function testValidationRejectsMalformedTranslations(mixed $value): void
    {
        $this->expectException(Exception::class);
        (new InputMultiLang(9102))->validate($value);
    }

    public static function invalidTranslations(): iterable
    {
        yield 'object' => [new \stdClass()];
        yield 'invalid json' => ['{invalid json'];
        yield 'scalar json' => ['"plain text"'];
        yield 'invalid language key' => [['de_DE' => 'Text']];
        yield 'numeric key' => [[1 => 'Text']];
    }

    public function testEmptyStateViewsAndSearchMetadataExposeFieldBehavior(): void
    {
        $Field = new InputMultiLang(9102, [
            'title' => ['de' => 'Titel'],
            'value' => []
        ]);

        self::assertTrue($Field->isEmpty());
        $Field->setValue(['de' => '', 'en' => 'Value']);
        self::assertFalse($Field->isEmpty());
        self::assertSame(9102, $Field->getBackendView()->getId());
        self::assertSame(9102, $Field->getFrontendView()->getId());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/InputMultiLang',
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
