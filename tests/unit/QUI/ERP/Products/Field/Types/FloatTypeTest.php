<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\FloatType;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

class FloatTypeTest extends TestCase
{
    public function testCleanupNormalizesPrecisionAndFormattedNumbers(): void
    {
        $Field = new FloatType(991031, ['name' => 'float']);

        self::assertSame(12.3457, $Field->cleanup(12.345678));
        self::assertSame(42.0, $Field->cleanup('42'));
        self::assertSame(1234.5679, $Field->cleanup('EUR 1.234,56789'));
        self::assertNull($Field->cleanup('not a number'));
    }

    public function testValidationAndEmptyStateReflectNormalizedFloatValues(): void
    {
        $Field = new FloatType(991031, ['name' => 'float']);

        $Field->validate(null);
        $Field->validate('12.5');
        self::assertTrue($Field->isEmpty());

        $Field->setValue('12.5');
        self::assertSame(12.5, $Field->getValue());
        self::assertFalse($Field->isEmpty());

        try {
            $Field->validate('invalid');
            self::fail('Non-numeric float values must be rejected.');
        } catch (Exception $Exception) {
            self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
        }
    }

    public function testViewsAndSearchMetadataExposeNumericFieldBehavior(): void
    {
        $Field = new FloatType(991031, [
            'name' => 'float',
            'public' => true,
            'value' => 1234.5
        ]);

        self::assertInstanceOf(View::class, $Field->getBackendView());
        $Frontend = $Field->getFrontendView();
        self::assertInstanceOf(View::class, $Frontend);
        self::assertSame(QUI::getLocale()->formatNumber(1234.5), $Frontend->getValue());
        self::assertStringContainsString('1234', str_replace(['.', ','], '', $Frontend->create()));
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/FloatType',
            $Field->getJavaScriptControl()
        );
        self::assertSame([
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTRANGE,
            Search::SEARCHTYPE_INPUTSELECTRANGE,
            Search::SEARCHTYPE_HASVALUE
        ], $Field->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_SELECTRANGE, $Field->getDefaultSearchType());
    }
}
