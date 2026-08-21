<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\FloatType;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\Types\IntType;
use QUI\ERP\Products\Field\Types\Textarea;
use QUI\ERP\Products\Field\Types\TimePeriod;
use QUI\ERP\Products\Field\Types\Url;

class FieldTypeNormalizationTest extends TestCase
{
    #[DataProvider('floatValues')]
    public function testFloatCleanup(mixed $input, ?float $expected): void
    {
        self::assertSame($expected, (new FloatType(9002))->cleanup($input));
    }

    public static function floatValues(): iterable
    {
        yield 'float precision' => [12.34567, 12.3457];
        yield 'plain numeric string' => ['1234', 1234.0];
        yield 'decorated value' => ['EUR 12.50', 12.5];
        yield 'empty value' => ['', null];
        yield 'non numeric value' => ['abc', null];
    }

    #[DataProvider('integerValues')]
    public function testIntegerCleanup(mixed $input, ?int $expected): void
    {
        self::assertSame($expected, (new IntType(9003))->cleanup($input));
    }

    public static function integerValues(): iterable
    {
        yield 'integer' => [42, 42];
        yield 'numeric string' => ['42', 42];
        yield 'decimal truncation' => ['42.9', 42];
        yield 'invalid' => ['forty-two', null];
        yield 'array' => [[], null];
    }

    public function testInputAndTextareaNormalizeScalarText(): void
    {
        $Input = new Input(9004);
        $Textarea = new Textarea(9005);

        self::assertSame('123', $Input->cleanup(123));
        self::assertSame('0', $Input->cleanup(0));
        self::assertNull($Input->cleanup([]));
        self::assertSame('content', $Textarea->cleanup('content'));
        self::assertNull($Textarea->cleanup(new \stdClass()));
    }

    public function testUrlAcceptsOnlyValidUrls(): void
    {
        $Url = new Url(9006);

        self::assertSame('https://www.quiqqer.com/products?id=1', $Url->cleanup(
            'https://www.quiqqer.com/products?id=1'
        ));
        self::assertNull($Url->cleanup('not a url'));

        $this->expectException(Exception::class);
        $Url->validate('not a url');
    }

    public function testTimePeriodNormalizesJsonAndArrayInput(): void
    {
        $Period = new TimePeriod(9007);

        self::assertSame(
            ['from' => 2, 'to' => 5, 'unit' => TimePeriod::PERIOD_DAY],
            $Period->cleanup('{"from":"2","to":"5","unit":"day"}')
        );
        self::assertSame(
            ['from' => 1, 'to' => 3, 'unit' => TimePeriod::PERIOD_MONTH],
            $Period->cleanup(['from' => 1, 'to' => 3, 'unit' => 'month'])
        );
        self::assertNull($Period->cleanup(['from' => 1, 'to' => 3, 'unit' => 'fortnight']));
        self::assertNull($Period->cleanup('invalid json'));
    }

    public function testTimePeriodValidationRequiresAllKeys(): void
    {
        $this->expectException(Exception::class);
        (new TimePeriod(9007))->validate(['from' => 1, 'to' => 2]);
    }
}
