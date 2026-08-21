<?php

namespace QUITests\ERP\Products\Unit;

use PHPUnit\Framework\TestCase;
use QUI\Controls\Sitemap\Item;
use QUI\Controls\Sitemap\Map;
use QUI\ERP\Products\ErpProvider;
use QUI\ERP\Products\NumberRange;

class ErpProviderTest extends TestCase
{
    public function testProviderCreatesProductMenuWithBothPanels(): void
    {
        $Map = new Map(['name' => 'root']);

        ErpProvider::addMenuItems($Map);

        $items = $Map->toArray()['items'];
        self::assertCount(1, $items);
        self::assertSame('products', $items[0]['name']);
        self::assertSame('fa fa-shopping-bag', $items[0]['icon']);
        self::assertTrue($items[0]['opened']);
        self::assertSame(2, $items[0]['priority']);
        self::assertSame(
            ['products-products', 'products-categories'],
            array_column($items[0]['items'], 'name')
        );
        self::assertSame(
            [
                'package/quiqqer/products/bin/controls/products/Panel',
                'package/quiqqer/products/bin/controls/categories/Panel'
            ],
            array_column($items[0]['items'], 'require')
        );
    }

    public function testProviderReusesExistingProductMenu(): void
    {
        $Map = new Map(['name' => 'root']);
        $Map->appendChild(new Item([
            'name' => 'products',
            'text' => 'Existing products section'
        ]));

        ErpProvider::addMenuItems($Map);

        $items = $Map->toArray()['items'];
        self::assertCount(1, $items);
        self::assertSame('Existing products section', $items[0]['text']);
        self::assertCount(2, $items[0]['items']);
    }

    public function testProviderPublishesProductsNumberRange(): void
    {
        $ranges = ErpProvider::getNumberRanges();

        self::assertCount(1, $ranges);
        self::assertInstanceOf(NumberRange::class, $ranges[0]);
    }
}
