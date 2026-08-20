<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\Types\Textarea;
use QUI\ERP\Products\Field\Types\UnitSelect;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Utils\Fields;

class FieldsTest extends TestCase
{
    public function testFieldSerializationValidatesValuesAndIgnoresInvalidEntries(): void
    {
        $Valid = new Input(991001, ['name' => 'valid', 'value' => 'steel']);
        $Invalid = new Input(991002, ['name' => 'invalid', 'value' => ['not', 'scalar']]);

        $serialized = Fields::parseFieldsToJson([$Valid, 'not-a-field', $Invalid]);

        self::assertCount(1, $serialized);
        self::assertSame(991001, $serialized[0]['id']);
        self::assertSame('steel', $serialized[0]['value']);
        self::assertTrue(Fields::isField($Valid));
        self::assertFalse(Fields::isField('not-a-field'));
    }

    public function testValidateFieldPropagatesConcreteValidationFailure(): void
    {
        $this->expectException(Exception::class);

        Fields::validateField(new Input(991003, [
            'name' => 'invalid',
            'value' => ['not', 'scalar']
        ]));
    }

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

    public function testSearchHashesNormalizeEmptyValueAndUnavailableField(): void
    {
        self::assertSame([';991099:*;'], Fields::getSearchHashesFromFieldHash(';991099:;'));
    }

    public function testFieldsCanBeSortedByPublicMetadataAndPriorityRules(): void
    {
        $First = new Input(991010, ['name' => 'item-10']);
        $Second = new Input(991002, ['name' => 'item-2']);
        $NoPriority = new Input(991003, ['name' => 'item-3']);
        $First->setAttribute('priority', 2);
        $Second->setAttribute('priority', 1);
        $NoPriority->setAttribute('priority', 0);

        self::assertSame(
            [991002, 991010, 991003],
            array_map(static fn ($Field): int => $Field->getId(), Fields::sortFields([
                $First,
                $NoPriority,
                $Second
            ]))
        );
        self::assertSame(
            [991002, 991003, 991010],
            array_map(static fn ($Field): int => $Field->getId(), Fields::sortFields([
                $First,
                $NoPriority,
                $Second
            ], 'id'))
        );
        self::assertSame(
            ['item-2', 'item-3', 'item-10'],
            array_map(static fn ($Field): string => $Field->getName(), Fields::sortFields([
                $First,
                $NoPriority,
                $Second
            ], 'name'))
        );
        self::assertSame(
            [991002, 991010, 991003],
            array_map(static fn ($Field): int => $Field->getId(), Fields::sortFields([
                $First,
                $NoPriority,
                $Second
            ], 'unsupported-sort'))
        );
    }

    public function testDetailFieldRulesRejectReservedAndUnsupportedFields(): void
    {
        $Regular = new Input(991020, ['name' => 'detail', 'showInDetails' => true]);
        $Hidden = new Input(991021, ['name' => 'hidden', 'showInDetails' => false]);
        $Reserved = new Input(FieldHandler::FIELD_TITLE, ['name' => 'title', 'showInDetails' => true]);
        $Textarea = new Textarea(991022, ['name' => 'textarea', 'showInDetails' => true]);

        self::assertTrue(Fields::canUsedAsDetailField($Regular));
        self::assertFalse(Fields::canUsedAsDetailField('not-a-field'));
        self::assertFalse(Fields::canUsedAsDetailField($Reserved));
        self::assertFalse(Fields::canUsedAsDetailField($Textarea));
        self::assertTrue(Fields::showFieldInProductDetails($Regular));
        self::assertFalse(Fields::showFieldInProductDetails($Hidden));
        self::assertFalse(Fields::showFieldInProductDetails($Reserved));
    }

    public function testWeightFieldConversionRequiresWeightFieldAndQuantity(): void
    {
        $Weight = new UnitSelect(FieldHandler::FIELD_WEIGHT, [
            'name' => 'weight',
            'value' => ['quantity' => 2500, 'id' => 'g']
        ]);
        $Empty = new UnitSelect(FieldHandler::FIELD_WEIGHT, ['name' => 'weight']);
        $Other = new UnitSelect(991023, [
            'name' => 'other',
            'value' => ['quantity' => 2500, 'id' => 'g']
        ]);

        self::assertSame(2.5, Fields::weightFieldToKilogram($Weight));
        self::assertSame(0, Fields::weightFieldToKilogram($Empty));
        self::assertSame(0, Fields::weightFieldToKilogram($Other));
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
