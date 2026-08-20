<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Date;
use QUI\ERP\Products\Field\Types\DateFrontendView;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

class DateTest extends TestCase
{
    public function testCleanupReturnsTimestampForValidDate(): void
    {
        $Date = new Date(9010, ['name' => 'delivery-date']);
        $expected = (new DateTimeImmutable('2024-02-29 12:34:56'))->getTimestamp();

        self::assertSame($expected, $Date->cleanup('2024-02-29 12:34:56'));
        $Date->validate('2024-02-29 12:34:56');
    }

    public function testValidationAcceptsEmptyValueAndRejectsInvalidDates(): void
    {
        $Date = new Date(9010, ['name' => 'delivery-date']);
        $Date->validate(null);

        foreach (['not-a-date', '2024-02-31'] as $value) {
            try {
                $Date->validate($value);
                self::fail("Invalid date '$value' must be rejected.");
            } catch (Exception $Exception) {
                self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
            }
        }
    }

    public function testCleanupFallsBackToCurrentTimestampForInvalidValue(): void
    {
        $before = time();
        $timestamp = (new Date(9010, ['name' => 'delivery-date']))->cleanup('not-a-date');
        $after = time();

        self::assertGreaterThanOrEqual($before, $timestamp);
        self::assertLessThanOrEqual($after, $timestamp);
    }

    public function testViewsAndSearchMetadataDescribeDateBehavior(): void
    {
        $timestamp = (new DateTimeImmutable('2024-02-29 12:34:56'))->getTimestamp();
        $Date = new Date(9010, [
            'name' => 'delivery-date',
            'public' => true,
            'value' => $timestamp
        ]);

        self::assertInstanceOf(View::class, $Date->getBackendView());
        $FrontendView = $Date->getFrontendView();
        self::assertInstanceOf(DateFrontendView::class, $FrontendView);
        self::assertStringContainsString('quiqqer-product-field-value', $FrontendView->create());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Date',
            $Date->getJavaScriptControl()
        );
        self::assertSame([
            Search::SEARCHTYPE_DATE,
            Search::SEARCHTYPE_DATERANGE
        ], $Date->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_DATERANGE, $Date->getDefaultSearchType());
    }
}
