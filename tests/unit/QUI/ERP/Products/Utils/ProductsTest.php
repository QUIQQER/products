<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Utils\Products;

class ProductsTest extends TestCase
{
    public function testVariantHashIsStableAcrossInputOrder(): void
    {
        $first = Products::generateVariantHashFromFields([
            10 => 'blue',
            2 => 42,
            7 => 'XL'
        ]);
        $second = Products::generateVariantHashFromFields([
            7 => 'XL',
            2 => 42,
            10 => 'blue'
        ]);

        self::assertSame(';2:42;7:584c;10:626c7565;', $first);
        self::assertSame($first, $second);
    }

    public function testVariantHashKeepsNumericValuesAndEncodesStrings(): void
    {
        self::assertSame(
            ';1:12.5;2:012;3:;',
            Products::generateVariantHashFromFields([
                1 => 12.5,
                2 => '012',
                3 => ''
            ])
        );
    }

    public function testEmptyVariantFieldsProduceWrappedEmptyHash(): void
    {
        self::assertSame(';;', Products::generateVariantHashFromFields([]));
    }
}
