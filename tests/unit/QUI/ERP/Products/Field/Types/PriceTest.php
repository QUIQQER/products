<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Price;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Search;
use QUI\ERP\Tax\Utils;

class PriceTest extends TestCase
{
    public function testCleanupNormalizesNumericValuesAndRejectsInvalidInput(): void
    {
        $Price = new Price(9011, ['name' => 'price']);

        self::assertNull($Price->cleanup([]));
        self::assertNull($Price->cleanup('   '));
        self::assertSame(12.5, $Price->cleanup(12.5));
        self::assertSame(12.5, $Price->cleanup('12.50'));
        self::assertNull($Price->cleanup('invalid'));
    }

    public function testValidationAndEmptyStateReflectNormalizedPrices(): void
    {
        $Price = new Price(9011, ['name' => 'price']);
        $Price->validate(null);
        $Price->validate('12.50');
        self::assertTrue($Price->isEmpty());

        $Price->setValue('12.50');
        self::assertSame(12.5, $Price->getValue());
        self::assertFalse($Price->isEmpty());

        $this->expectException(Exception::class);
        $Price->validate(['12.50']);
    }

    public function testControlsViewsAndSearchMetadataDescribePriceBehavior(): void
    {
        $Price = new Price(9011, [
            'name' => 'price',
            'public' => true,
            'value' => 12.5
        ]);

        self::assertInstanceOf(View::class, $Price->getBackendView());
        self::assertInstanceOf(View::class, $Price->getFrontendView());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Price',
            $Price->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/PriceSettings',
            $Price->getJavaScriptSettings()
        );
        self::assertSame('', (new Price(Fields::FIELD_PRICE, ['name' => 'price']))->getJavaScriptSettings());
        self::assertSame([
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTRANGE,
            Search::SEARCHTYPE_INPUTSELECTRANGE,
            Search::SEARCHTYPE_HASVALUE
        ], $Price->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_SELECTRANGE, $Price->getDefaultSearchType());
    }

    public function testCalculatedValueRangesCoverEmptyFractionalAndMagnitudeValues(): void
    {
        $Price = new Price(9011, ['name' => 'price']);

        self::assertSame([0], $Price->calculateValueRange(null, null));

        $fractionalRange = $Price->calculateValueRange(0.2, 0.5);
        self::assertSame(0, $fractionalRange[0]);
        self::assertSame(0.1, $fractionalRange[1]);
        self::assertGreaterThanOrEqual(0.5 * ((100 + Utils::getMaxTax()) / 100), end($fractionalRange));

        $magnitudeRange = $Price->calculateValueRange(144, 255);
        self::assertSame(140, $magnitudeRange[0]);
        self::assertSame(200.0, $magnitudeRange[1]);
        self::assertGreaterThanOrEqual(255 * ((100 + Utils::getMaxTax()) / 100), end($magnitudeRange));
        self::assertLessThan(255 * ((100 + Utils::getMaxTax()) / 100), $magnitudeRange[count($magnitudeRange) - 2]);
    }
}
