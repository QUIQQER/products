<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\IntType;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

class IntTypeTest extends TestCase
{
    public function testCleanupNormalizesSignedDecimalAndScientificValues(): void
    {
        $Field = new IntType(991032, ['name' => 'quantity']);

        self::assertSame(42, $Field->cleanup('42'));
        self::assertSame(-42, $Field->cleanup('-42.9'));
        self::assertSame(1000, $Field->cleanup('1e3'));
        self::assertSame(PHP_INT_MAX, $Field->cleanup((string)PHP_INT_MAX));
        self::assertNull($Field->cleanup('42 items'));
        self::assertNull($Field->cleanup([]));
    }

    public function testSetValueRejectsNonNumericInputWithoutReplacingCurrentValue(): void
    {
        $Field = new IntType(991032, ['name' => 'quantity']);

        $Field->setValue('-12.75');
        self::assertSame(-12, $Field->getValue());

        try {
            $Field->setValue('twelve');
            self::fail('Non-numeric integer values must be rejected.');
        } catch (Exception $Exception) {
                self::assertSame(
                    \QUI::getLocale()->get('quiqqer/products', 'exception.field.invalid', [
                        'fieldId' => $Field->getId(),
                        'fieldTitle' => $Field->getTitle(),
                        'fieldType' => $Field->getType()
                    ]),
                    $Exception->getMessage()
                );
        }

        self::assertSame(-12, $Field->getValue());

        $Field->setValue('0');
        self::assertSame(0, $Field->getValue());
        self::assertTrue($Field->isEmpty());
    }

    public function testViewsAndSearchMetadataExposeIntegerFieldBehavior(): void
    {
        $Field = new IntType(991032, [
            'name' => 'quantity',
            'public' => true,
            'value' => -125
        ]);

        $Backend = $Field->getBackendView();
        $Frontend = $Field->getFrontendView();

        self::assertInstanceOf(View::class, $Backend);
        self::assertSame(-125, $Backend->getValue());
        self::assertInstanceOf(View::class, $Frontend);
        self::assertSame(-125, $Frontend->getValue());
        self::assertStringContainsString(
            '<div class="quiqqer-product-field-value">-125</div>',
            $Frontend->create()
        );
        self::assertSame('BIGINT', $Field->getColumnType());
        self::assertSame(Search::SEARCHDATATYPE_NUMERIC, $Field->getSearchDataType());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/IntType',
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
