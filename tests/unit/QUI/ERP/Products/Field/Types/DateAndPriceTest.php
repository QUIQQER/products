<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Date;
use QUI\ERP\Products\Field\Types\Price;

class DateAndPriceTest extends TestCase
{
    public function testDateCleanupReturnsTimestampForValidDate(): void
    {
        $Date = new Date(9010);
        $expected = (new DateTimeImmutable('2024-02-29 12:34:56'))->getTimestamp();

        self::assertSame($expected, $Date->cleanup('2024-02-29 12:34:56'));
        $Date->validate('2024-02-29 12:34:56');
        self::addToAssertionCount(1);
    }

    public function testDateValidationRejectsImpossibleDate(): void
    {
        $this->expectException(Exception::class);
        (new Date(9010))->validate('2024-02-31');
    }

    public function testPriceCleanupRejectsArraysAndWhitespace(): void
    {
        $Price = new Price(9011);

        self::assertNull($Price->cleanup([]));
        self::assertNull($Price->cleanup('   '));
    }

    public function testPriceCleanupNormalizesNumericValues(): void
    {
        $Price = new Price(9011);

        self::assertSame(12.5, $Price->cleanup(12.5));
        self::assertSame(12.5, $Price->cleanup('12.50'));
        self::assertNull($Price->cleanup('invalid'));
    }

    public function testPriceValidationRejectsNonNumericArray(): void
    {
        $this->expectException(Exception::class);
        (new Price(9011))->validate(['12.50']);
    }
}
