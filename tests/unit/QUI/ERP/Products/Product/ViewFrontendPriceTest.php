<?php

namespace QUITests\ERP\Products\Unit\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Money\Price;
use QUI\ERP\Products\Product\Types\Product;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Products\Product\ViewFrontend;
use QUI\ERP\Products\Utils\Package;
use ReflectionProperty;

class ViewFrontendPriceTest extends TestCase
{
    #[DataProvider('priceRangeProvider')]
    public function testStartingPriceDependsOnDisplayedValues(
        string $productClass,
        float $minimum,
        float $maximum,
        bool $starting
    ): void {
        $Visibility = new ReflectionProperty(Package::class, 'hidePrice');
        $originalVisibility = $Visibility->getValue();
        $Visibility->setValue(null, false);
        $Currency = QUI\ERP\Defaults::getCurrency();
        $MinimumPrice = new Price($minimum, $Currency);
        $MaximumPrice = new Price($maximum, $Currency);
        $MinimumPrice->enableMinimalPrice();
        $Product = $this->createMock($productClass);
        $Product->method('isActive')->willReturn(true);
        $Product->method('getPermissions')->willReturn([]);
        $Product->method('getMinimumPrice')->with(QUI::getUserBySession())->willReturn($MinimumPrice);
        $Product->method('getMaximumPrice')->with(QUI::getUserBySession())->willReturn($MaximumPrice);

        if (!$Product instanceof VariantParent) {
            $Unique = $this->createMock(UniqueProduct::class);
            $Unique->method('calc')->willReturnSelf();
            $Unique->method('getPrice')->willReturn($MinimumPrice);
            $Product->method('createUniqueProduct')->willReturn($Unique);
        }

        try {
            $Price = (new ViewFrontend($Product))->getPrice();
            self::assertSame($minimum, $Price->value());
            self::assertSame($starting, $Price->isMinimalPrice());
        } finally {
            $Visibility->setValue(null, $originalVisibility);
        }
    }

    /** @return array<string, array{class-string<Product>, float, float, bool}> */
    public static function priceRangeProvider(): array
    {
        return [
            'different variant prices' => [VariantParent::class, 19.9, 29.9, true],
            'same values in separate price objects' => [VariantParent::class, 19.9, 19.9, false],
            'same displayed cents' => [VariantParent::class, 19.90001, 19.90002, false],
            'invalid inverted range' => [VariantParent::class, 29.9, 19.9, false],
            'product with variable options' => [Product::class, 19.9, 29.9, true],
            'fixed product' => [Product::class, 19.9, 19.9, false],
            'specific fixed variant' => [VariantChild::class, 19.9, 19.9, false]
        ];
    }
}
