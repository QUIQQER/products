<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Utils\Fields;

class FieldsTest extends TestCase
{
    public function testFieldHashParserHandlesWrappedAndEmptyHashes(): void
    {
        self::assertSame([12 => 'red', 3 => 'large'], Fields::parseFieldHashToArray(';12:red;3:large;'));
        self::assertSame([], Fields::parseFieldHashToArray(''));
        self::assertSame([], Fields::parseFieldHashToArray(';;;'));
    }

    public function testSearchHashesReplaceOneValueAtATimeWithWildcard(): void
    {
        self::assertSame(
            [';12:*;3:large;', ';12:red;3:*;'],
            Fields::getSearchHashesFromFieldHash(';12:red;3:large;')
        );
    }

    #[DataProvider('weightConversions')]
    public function testWeightConversion(float|int|string $value, string $unit, float|int $expected): void
    {
        self::assertEqualsWithDelta($expected, Fields::weightToKilogram($value, $unit), 0.00001);
    }

    public static function weightConversions(): iterable
    {
        yield 'kilogram' => [2.5, 'kg', 2.5];
        yield 'gram' => [2500, 'g', 2.5];
        yield 'metric ton' => [1.5, 't', 1500.0];
        yield 'tons alias' => [2, 'tons', 2000.0];
        yield 'pound' => [2.2046, 'lb', 1.0];
        yield 'pounds alias' => [4.4092, 'lbs', 2.0];
        yield 'unknown unit' => [12, 'stone', 12.0];
        yield 'empty unit' => ['12.5', '', 12.5];
    }

    #[DataProvider('weightUnits')]
    public function testWeightUnitDetection(string $unit, bool $expected): void
    {
        self::assertSame($expected, Fields::isWeight($unit));
    }

    public static function weightUnits(): iterable
    {
        yield ['g', true];
        yield ['kg', true];
        yield ['t', true];
        yield ['tons', true];
        yield ['lb', true];
        yield ['lbs', true];
        yield ['stone', false];
        yield ['', false];
    }

    #[DataProvider('comparisons')]
    public function testComparisonTerms(mixed $left, mixed $right, string $operator, bool $expected): void
    {
        self::assertSame($expected, Fields::compare($left, $right, $operator));
    }

    public static function comparisons(): iterable
    {
        yield 'equal' => [5, '5', '=', true];
        yield 'greater than' => [6, 5, 'gt', true];
        yield 'greater than false' => [5, 5, 'gt', false];
        yield 'greater or equal' => [5, 5, 'egt', true];
        yield 'less than' => [4, 5, 'lt', true];
        yield 'less or equal' => [5, 5, 'elt', true];
        yield 'unknown operator' => [5, 5, '!=', false];
    }

    #[DataProvider('humanTerms')]
    public function testHumanReadableTerms(string $term, string $expected): void
    {
        self::assertSame($expected, Fields::termToHuman($term));
    }

    public static function humanTerms(): iterable
    {
        yield ['=', '='];
        yield ['gt', '>'];
        yield ['egt', '>='];
        yield ['lt', '<'];
        yield ['elt', '<='];
        yield ['unknown', ''];
    }
}
