<?php

namespace QUITests\ERP\Products\Integration\Controls;

use QUI\ERP\Products\Controls\Products\ProductPicker;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductPickerTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testPickerRendersPersistedProductsAcrossConfiguredSheets(): void
    {
        $Monthly = ProductTestHelper::createProduct('picker-monthly', 12.5);
        $Yearly = ProductTestHelper::createProduct('picker-yearly', 120.0);

        foreach ([$Monthly, $Yearly] as $Product) {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                $Product->activate($SystemUser);
            });
        }

        $Control = new ProductPicker([
            'sheetOptionsStyle' => 'button-style1',
            'sheetOptions' => [
                ['id' => 'monthly', 'label' => 'Monthly'],
                ['id' => 'yearly', 'label' => 'Yearly']
            ],
            'sheets' => [[
                'title' => 'Subscription',
                'content' => 'Choose a billing interval',
                'highlighted' => true,
                'options' => [
                    'monthly' => $Monthly->getId(),
                    'yearly' => $Yearly->getId()
                ]
            ]],
            'showProductDetails' => false,
            'zeroProduct' => true
        ]);

        $html = $Control->create();

        self::assertStringContainsString('productPicker__options--button-style1', $html);
        self::assertStringContainsString('value="monthly"', $html);
        self::assertStringContainsString('value="yearly"', $html);
        self::assertStringContainsString('Subscription', $html);
        self::assertStringContainsString('Choose a billing interval', $html);
        self::assertStringContainsString('data-product-id="' . $Monthly->getId() . '"', $html);
        self::assertStringContainsString('data-product-id="' . $Yearly->getId() . '"', $html);
        self::assertStringContainsString('data-name="zero-product-title"', $html);
        self::assertStringNotContainsString('picker-monthly', $html);
        self::assertStringNotContainsString('picker-yearly', $html);
        self::assertStringContainsString('--_q-conf--countSheets', $html);
    }

    public function testPickerShowsRadioOptionsAndPriceWithoutProductDetails(): void
    {
        $Product = ProductTestHelper::createProduct('picker-visible-details', 19.95);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Control = new ProductPicker([
            'sheetOptionsStyle' => 'radio',
            'sheetOptions' => [['id' => 'once', 'label' => 'One time']],
            'sheets' => [[
                'title' => 'Purchase',
                'highlighted' => false,
                'options' => ['once' => $Product->getId()]
            ]],
            'showProductDetails' => false
        ]);

        $html = $Control->create();

        self::assertStringContainsString('productPicker__options--radio', $html);
        self::assertStringContainsString('type="radio"', $html);
        self::assertStringContainsString('data-product-id="' . $Product->getId() . '"', $html);
        self::assertStringContainsString('19,95', $html);
        self::assertStringNotContainsString('picker-visible-details', $html);
    }

    public function testInvalidCollectionsAndStyleFallBackToEmptySelect(): void
    {
        $Control = new ProductPicker([
            'sheetOptionsStyle' => 'unsupported',
            'sheetOptions' => 'invalid',
            'sheets' => 'invalid'
        ]);

        $html = $Control->create();

        self::assertStringContainsString('productPicker__options--select', $html);
        self::assertStringContainsString('<select name="interval"', $html);
        self::assertStringNotContainsString('data-name="product-sheet-product"', $html);
        self::assertStringNotContainsString('--_q-conf--countSheets', $html);
    }
}
