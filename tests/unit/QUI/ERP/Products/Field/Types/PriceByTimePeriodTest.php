<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\PriceByTimePeriod;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Interfaces\ProductInterface;

class PriceByTimePeriodTest extends TestCase
{
    public function testPriceIsReturnedOnlyInsideConfiguredPeriod(): void
    {
        $Field = new PriceByTimePeriod(9207);
        $Product = $this->createMock(ProductInterface::class);
        $Product->method('getFieldValue')->with(9207)->willReturn([
            'price' => 17.5,
            'from' => (new DateTimeImmutable('-1 day'))->format('Y-m-d'),
            'to' => (new DateTimeImmutable('+1 day'))->format('Y-m-d')
        ]);

        self::assertSame(17.5, $Field->getValueDependendByProduct($Product));
        self::assertSame(17.5, $Field->onGetPriceFieldForProduct($Product));
    }

    public function testFutureExpiredAndMissingPeriodsDoNotChangePrice(): void
    {
        $Field = new PriceByTimePeriod(9207);
        $Product = $this->createMock(ProductInterface::class);
        $Product->method('getFieldValue')->willReturnOnConsecutiveCalls(
            [
                'price' => 17.5,
                'from' => (new DateTimeImmutable('+1 day'))->format('Y-m-d'),
                'to' => false
            ],
            [
                'price' => 17.5,
                'from' => false,
                'to' => (new DateTimeImmutable('-1 day'))->format('Y-m-d')
            ],
            '',
            'invalid-json'
        );

        self::assertFalse($Field->getValueDependendByProduct($Product));
        self::assertFalse($Field->getValueDependendByProduct($Product));
        self::assertFalse($Field->getValueDependendByProduct($Product));
        self::assertFalse($Field->getValueDependendByProduct($Product));
    }

    #[DataProvider('cleanupValues')]
    public function testCleanupNormalizesPriceAndDates(mixed $input, array $expected): void
    {
        self::assertSame($expected, (new PriceByTimePeriod(9207))->cleanup($input));
    }

    public static function cleanupValues(): iterable
    {
        yield 'float and dates' => [
            ['price' => 12.5, 'from' => '2026-01-02 10:15', 'to' => '2026-02-03 20:30'],
            ['price' => 12.5, 'from' => '2026-01-02 10:15', 'to' => '2026-02-03 20:30']
        ];
        yield 'localized price and open period' => [
            '{"price":"12,50","from":false,"to":false}',
            ['price' => 12.5, 'from' => false, 'to' => false]
        ];
        yield 'incomplete value' => [
            ['price' => 10],
            ['price' => '', 'from' => false, 'to' => false]
        ];
        yield 'invalid json' => [
            'invalid-json',
            ['price' => '', 'from' => false, 'to' => false]
        ];
    }

    public function testValidationAcceptsCompleteValuesAndRejectsMalformedInput(): void
    {
        $Field = new PriceByTimePeriod(9207);
        $Field->validate(['price' => 5, 'from' => false, 'to' => false]);
        $Field->validate('{"price":5,"from":false,"to":false}');
        self::assertSame(
            ['price' => 5.0, 'from' => false, 'to' => false],
            $Field->cleanup('{"price":5,"from":false,"to":false}')
        );

        try {
            $Field->validate('invalid-json');
            self::fail('Malformed JSON must be rejected.');
        } catch (Exception $Exception) {
            self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
        }

        $this->expectException(Exception::class);
        $Field->validate(['price' => 5]);
    }

    public function testViewsAndControlMetadataExposeNormalizedPrice(): void
    {
        $Field = new PriceByTimePeriod(9207, [
            'value' => [
                'price' => 12.5,
                'from' => false,
                'to' => false
            ]
        ]);

        self::assertInstanceOf(View::class, $Field->getBackendView());
        self::assertInstanceOf(View::class, $Field->getFrontendView());
        self::assertStringContainsString('12,5', (string)$Field->getFrontendView()->getValue());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriodSettings',
            $Field->getJavaScriptSettings()
        );
    }

    public function testEmptyStateDependsOnConfiguredPrice(): void
    {
        self::assertTrue((new PriceByTimePeriod(9207))->isEmpty());
        self::assertTrue((new PriceByTimePeriod(9207, [
            'value' => ['price' => 0, 'from' => false, 'to' => false]
        ]))->isEmpty());
        self::assertFalse((new PriceByTimePeriod(9207, [
            'value' => '{"price":5,"from":false,"to":false}'
        ]))->isEmpty());
    }
}
