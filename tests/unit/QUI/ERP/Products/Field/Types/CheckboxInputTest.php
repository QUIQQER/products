<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\CheckboxInput;

class CheckboxInputTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testCleanupKeepsCompleteCheckboxValues(mixed $value, array $expected): void
    {
        $Field = new CheckboxInput(9104);

        $Field->validate($value);
        self::assertSame($expected, $Field->cleanup($value));
    }

    public static function validValues(): iterable
    {
        yield 'array checked' => [
            ['checked' => true, 'value' => 'Engraving'],
            ['checked' => true, 'value' => 'Engraving']
        ];
        yield 'array unchecked' => [
            ['checked' => false, 'value' => ''],
            ['checked' => false, 'value' => '']
        ];
        yield 'json' => [
            '{"checked":true,"value":"Gift wrap"}',
            ['checked' => true, 'value' => 'Gift wrap']
        ];
    }

    #[DataProvider('invalidValues')]
    public function testCleanupReturnsDefaultForMalformedValues(mixed $value): void
    {
        $Field = new CheckboxInput(9104);

        self::assertSame(
            ['checked' => false, 'value' => ''],
            $Field->cleanup($value)
        );
    }

    public static function invalidValues(): iterable
    {
        yield 'invalid json' => ['{invalid json'];
        yield 'empty value' => [''];
        yield 'missing checked' => [['value' => 'Text']];
        yield 'missing value' => [['checked' => true]];
        yield 'scalar json' => ['"text"'];
        yield 'integer' => [42];
    }

    #[DataProvider('invalidValidationValues')]
    public function testValidationRejectsMalformedCheckboxValues(mixed $value): void
    {
        $this->expectException(Exception::class);
        (new CheckboxInput(9104))->validate($value);
    }

    public static function invalidValidationValues(): iterable
    {
        yield 'invalid json' => ['{invalid json'];
        yield 'missing checked' => [['value' => 'Text']];
        yield 'missing value' => [['checked' => true]];
        yield 'scalar json' => ['"text"'];
        yield 'integer' => [42];
    }

    public function testControlMetadataIdentifiesCheckboxInput(): void
    {
        $Field = new CheckboxInput(9104);

        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/CheckboxInput',
            $Field->getJavaScriptControl()
        );
    }
}
