<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Types\PriceByQuantity;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Interfaces\ProductInterface;

class PriceByQuantityTest extends TestCase
{
    #[DataProvider('cleanupValues')]
    public function testCleanupNormalizesPriceAndQuantity(mixed $input, array $expected): void
    {
        self::assertSame($expected, (new PriceByQuantity(9208))->cleanup($input));
    }

    public static function cleanupValues(): iterable
    {
        yield 'float price' => [
            ['price' => 12.5, 'quantity' => '3'],
            ['price' => 12.5, 'quantity' => 3.0]
        ];
        yield 'localized price' => [
            '{"price":"12,50","quantity":"4"}',
            ['price' => 12.5, 'quantity' => 4.0]
        ];
        yield 'missing quantity' => [
            ['price' => 12.5],
            ['price' => '', 'quantity' => '']
        ];
        yield 'zero quantity' => [
            ['price' => 12.5, 'quantity' => 0],
            ['price' => '', 'quantity' => '']
        ];
        yield 'zero price' => [
            ['price' => 0, 'quantity' => 3],
            ['price' => '', 'quantity' => '']
        ];
        yield 'invalid json' => [
            'invalid-json',
            ['price' => '', 'quantity' => '']
        ];
    }

    public function testOrdinaryProductDoesNotReceiveQuantityPrice(): void
    {
        $Field = new PriceByQuantity(9208);
        $Product = $this->createMock(ProductInterface::class);

        self::assertFalse($Field->getValueDependendByProduct($Product));
        self::assertFalse($Field->onGetPriceFieldForProduct($Product));
    }

    public function testViewsAndControlMetadataExposeNormalizedRule(): void
    {
        $Field = new PriceByQuantity(9208, [
            'value' => ['price' => 12.5, 'quantity' => 3]
        ]);

        $Field->validate(['price' => 12.5, 'quantity' => 3]);
        self::assertInstanceOf(View::class, $Field->getBackendView());
        self::assertInstanceOf(View::class, $Field->getFrontendView());
        $Price = new \QUI\ERP\Money\Price(
            12.5,
            \QUI\ERP\Defaults::getCurrency()
        );
        self::assertSame(
            \QUI::getLocale()->get('quiqqer/products', 'fieldtype.PriceByQuantity.frontend.text', [
                'price' => $Price->getDisplayPrice(),
                'quantity' => 3.0
            ]),
            $Field->getFrontendView()->getValue()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/PriceByQuantity',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/PriceByQuantitySettings',
            $Field->getJavaScriptSettings()
        );
    }

    public function testEmptyStateRequiresNonZeroPriceAndQuantityKeys(): void
    {
        self::assertTrue((new PriceByQuantity(9208))->isEmpty());
        self::assertTrue((new PriceByQuantity(9208, [
            'value' => ['price' => 0, 'quantity' => 2]
        ]))->isEmpty());
        self::assertFalse((new PriceByQuantity(9208, [
            'value' => '{"price":5,"quantity":2}'
        ]))->isEmpty());
    }
}
