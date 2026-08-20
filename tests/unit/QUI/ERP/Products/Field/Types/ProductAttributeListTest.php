<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\ProductAttributeList;
use QUI\ERP\Products\Field\Types\ProductAttributeListBackendView;
use QUI\ERP\Products\Field\Types\ProductAttributeListFrontendView;

class ProductAttributeListTest extends TestCase
{
    public function testDefaultSelectionAndEntryManagement(): void
    {
        $Field = $this->createField();

        self::assertSame(1, $Field->getValue());
        self::assertTrue($Field->isCustomField());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/ProductAttributeList',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings',
            $Field->getJavaScriptSettings()
        );

        $Field->addEntry([]);
        $Field->addEntry(['title' => 'missing sum']);
        $Field->addEntry(['sum' => 2]);
        self::assertCount(3, $Field->getOption('entries'));

        $Field->addEntry([
            'title' => 'Added option',
            'sum' => 4,
            'type' => ErpCalc::CALCULATION_COMPLEMENT,
            'userinput' => true,
            'ignored' => 'must not be persisted'
        ]);
        $entries = $Field->getOption('entries');
        self::assertCount(4, $entries);
        self::assertSame('Added option', $entries[3]['title']);
        self::assertArrayNotHasKey('ignored', $entries[3]);

        $Field->disableEntries();
        self::assertTrue($Field->getOption('entries')[0]['disabled']);
        self::assertTrue($Field->getOption('entries')[3]['disabled']);
        $Field->enableEntry(3);
        self::assertFalse($Field->getOption('entries')[3]['disabled']);
        $Field->disableEntry(3);
        self::assertTrue($Field->getOption('entries')[3]['disabled']);
    }

    public function testCalculationDataUsesSelectedOptionAndEscapesUserInput(): void
    {
        $Field = $this->createField();
        $Field->setValue('[2,"<script>alert(1)</script>"]');

        self::assertSame('<script>alert(1)</script>', $Field->getUserInput());

        $calculation = $Field->getCalculationData();
        self::assertSame(7, $calculation['priority']);
        self::assertSame('calculated', $calculation['basis']);
        self::assertSame(10, $calculation['value']);
        self::assertSame(ErpCalc::CALCULATION_PERCENTAGE, $calculation['calculation']);
        self::assertSame('Premium - &lt;script&gt;alert(1)&lt;/script&gt;', $calculation['valueText']);
        self::assertTrue($calculation['displayDiscounts']);

        $attributes = $Field->getAttributes();
        self::assertSame($calculation, $attributes['custom_calc']);
        self::assertSame($calculation['valueText'], $attributes['valueText']);
    }

    public function testViewsPreserveFieldValueAndOptions(): void
    {
        $Field = $this->createField();
        $Field->setValue(2);

        $Frontend = $Field->getFrontendView();
        $Backend = $Field->getBackendView();

        self::assertInstanceOf(ProductAttributeListFrontendView::class, $Frontend);
        self::assertInstanceOf(ProductAttributeListBackendView::class, $Backend);
        self::assertSame(2, $Frontend->getValue());
        self::assertSame(2, $Backend->getValue());
        self::assertSame($Field->getOptions(), $Frontend->getOptions());
        self::assertSame($Field->getOptions(), $Backend->getOptions());
    }

    #[DataProvider('cleanupValues')]
    public function testCleanupNormalizesSupportedRepresentations(mixed $input, mixed $expected): void
    {
        self::assertSame($expected, $this->createField()->cleanup($input));
    }

    public static function cleanupValues(): iterable
    {
        yield 'empty string' => ['', null];
        yield 'integer' => [2, 2];
        yield 'numeric string' => ['2', 2];
        yield 'zero' => [0, 0];
        yield 'json user input' => ['[2,"engraving"]', '[2,"engraving"]'];
        yield 'array user input' => [[2, 'engraving'], [2, 'engraving']];
        yield 'text containing an integer' => ['option 2', null];
        yield 'invalid json' => ['unknown', null];
        yield 'incomplete json' => ['[2]', null];
        yield 'non numeric json key' => ['["second","engraving"]', null];
        yield 'invalid array' => [['second', 'engraving'], null];
        yield 'boolean' => [true, null];
    }

    public function testValidationAcceptsKnownOptionsAndRejectsUnknownOptions(): void
    {
        $Field = $this->createField();
        $Field->validate(0);
        $Field->validate('2');
        $Field->validate('[2,"engraving"]');

        $this->expectException(Exception::class);
        $Field->validate(99);
    }

    private function createField(): ProductAttributeList
    {
        return new ProductAttributeList(9201, [
            'public' => true,
            'required' => true,
            'options' => [
                'priority' => 7,
                'calculation_basis' => 'calculated',
                'display_discounts' => true,
                'entries' => [
                    [
                        'title' => ['de' => 'Standard'],
                        'sum' => 0,
                        'type' => ErpCalc::CALCULATION_COMPLEMENT
                    ],
                    [
                        'title' => ['de' => 'Comfort'],
                        'sum' => 5,
                        'type' => ErpCalc::CALCULATION_COMPLEMENT,
                        'selected' => true
                    ],
                    [
                        'title' => ['de' => 'Premium'],
                        'sum' => 10,
                        'type' => ErpCalc::CALCULATION_PERCENTAGE,
                        'userinput' => true
                    ]
                ]
            ]
        ]);
    }
}
