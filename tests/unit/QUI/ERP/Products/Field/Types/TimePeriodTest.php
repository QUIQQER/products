<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\TimePeriod;
use QUI\ERP\Products\Field\Types\UnitSelectFrontendView;
use QUI\ERP\Products\Field\View;

class TimePeriodTest extends TestCase
{
    #[DataProvider('validPeriods')]
    public function testValidPeriodsAreValidatedAndNormalized(mixed $input, array $expected): void
    {
        $Field = new TimePeriod(991040, ['name' => 'period']);

        $Field->validate($input);

        self::assertSame($expected, $Field->cleanup($input));
    }

    public static function validPeriods(): iterable
    {
        yield 'seconds from JSON' => [
            '{"from":"1","to":"30","unit":"second"}',
            ['from' => 1, 'to' => 30, 'unit' => TimePeriod::PERIOD_SECOND]
        ];
        yield 'minutes' => [
            ['from' => 2, 'to' => 45, 'unit' => TimePeriod::PERIOD_MINUTE],
            ['from' => 2, 'to' => 45, 'unit' => TimePeriod::PERIOD_MINUTE]
        ];
        yield 'hours' => [
            ['from' => 0, 'to' => 23, 'unit' => TimePeriod::PERIOD_HOUR],
            ['from' => 0, 'to' => 23, 'unit' => TimePeriod::PERIOD_HOUR]
        ];
        yield 'days' => [
            ['from' => -2, 'to' => 5, 'unit' => TimePeriod::PERIOD_DAY],
            ['from' => -2, 'to' => 5, 'unit' => TimePeriod::PERIOD_DAY]
        ];
        yield 'weeks' => [
            ['from' => 1.9, 'to' => '4.8', 'unit' => TimePeriod::PERIOD_WEEK],
            ['from' => 1, 'to' => 4, 'unit' => TimePeriod::PERIOD_WEEK]
        ];
        yield 'months' => [
            ['from' => '1', 'to' => '12', 'unit' => TimePeriod::PERIOD_MONTH],
            ['from' => 1, 'to' => 12, 'unit' => TimePeriod::PERIOD_MONTH]
        ];
        yield 'years' => [
            ['from' => 2025, 'to' => 2030, 'unit' => TimePeriod::PERIOD_YEAR],
            ['from' => 2025, 'to' => 2030, 'unit' => TimePeriod::PERIOD_YEAR]
        ];
    }

    public function testValidationRejectsMalformedValuesWithFieldException(): void
    {
        $Field = new TimePeriod(991040, ['name' => 'period']);
        $invalidValues = [
            42,
            new \stdClass(),
            'invalid-json',
            '42',
            'true',
            ['from' => 1, 'to' => 2],
            ['from' => 1, 'unit' => TimePeriod::PERIOD_DAY],
            ['to' => 2, 'unit' => TimePeriod::PERIOD_DAY]
        ];

        foreach ($invalidValues as $invalidValue) {
            try {
                $Field->validate($invalidValue);
                self::fail('Malformed time periods must be rejected.');
            } catch (Exception $Exception) {
                self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
            }
        }
    }

    public function testCleanupFallsBackToConfiguredDefaultForInvalidValues(): void
    {
        $default = [
            'from' => 1,
            'to' => 7,
            'unit' => TimePeriod::PERIOD_DAY
        ];
        $Field = new TimePeriod(991040, [
            'name' => 'period',
            'defaultValue' => $default
        ]);

        self::assertSame($default, $Field->cleanup(null));
        self::assertSame($default, $Field->cleanup([]));
        self::assertSame($default, $Field->cleanup(new \stdClass()));
        self::assertSame($default, $Field->cleanup('invalid-json'));
        self::assertSame($default, $Field->cleanup('42'));
        self::assertSame($default, $Field->cleanup('true'));
        self::assertSame($default, $Field->cleanup([
            'from' => 1,
            'to' => 2,
            'unit' => 'fortnight'
        ]));
    }

    public function testViewsAndMetadataExposeTheConfiguredPeriod(): void
    {
        $value = [
            'from' => 1,
            'to' => 3,
            'unit' => TimePeriod::PERIOD_MONTH
        ];
        $Field = new TimePeriod(991040, [
            'name' => 'period',
            'public' => true,
            'value' => $value
        ]);

        $BackendView = $Field->getBackendView();
        self::assertInstanceOf(View::class, $BackendView);
        self::assertSame($value, $BackendView->getValue());

        $FrontendView = $Field->getFrontendView();
        self::assertInstanceOf(UnitSelectFrontendView::class, $FrontendView);
        self::assertSame($value, $FrontendView->getValue());
        self::assertFalse($Field->isSearchable());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/TimePeriod',
            $Field->getJavaScriptControl()
        );
    }
}
