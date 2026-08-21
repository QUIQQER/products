<?php

namespace QUITests\ERP\Products\Integration;

use QUI\ERP\Products\DesktopSearch;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class DesktopSearchTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testProductIdSearchReturnsDesktopResultWithOpenParameters(): void
    {
        $Product = ProductTestHelper::createProduct('desktop-search-product', 14.5);
        $Provider = new DesktopSearch();

        $result = $Provider->search('#' . $Product->getId(), [
            'filterGroups' => ['pages', DesktopSearch::TYPE]
        ]);

        self::assertCount(1, $result);
        self::assertSame($Product->getId(), $result[0]['id']);
        self::assertSame('desktop-search-product', $result[0]['title']);
        self::assertSame('', $result[0]['description']);
        self::assertSame('fa fa-shopping-bag', $result[0]['icon']);
        self::assertSame(DesktopSearch::TYPE, $result[0]['group']);
        self::assertNotSame('', $result[0]['groupLabel']);

        $entry = json_decode(
            $Provider->getEntry($Product->getId())['searchdata'],
            true,
            flags: JSON_THROW_ON_ERROR
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/products/Product',
            $entry['require']
        );
        self::assertSame($Product->getId(), $entry['params']['productId']);
    }

    public function testForeignFilterGroupAndUnknownProductProduceStableEmptyResults(): void
    {
        $Provider = new DesktopSearch();

        self::assertSame([], $Provider->search('anything', ['filterGroups' => ['pages']]));
        self::assertSame([], $Provider->search('#999999'));
    }

    public function testFilterMetadataAndCacheBuildRemainStable(): void
    {
        $Provider = new DesktopSearch();
        $before = $Provider->getFilterGroups();

        $Provider->buildCache();

        self::assertSame($before, $Provider->getFilterGroups());
        self::assertSame([
            [
                'group' => DesktopSearch::TYPE,
                'label' => [
                    'quiqqer/products',
                    'search.group.products.label'
                ]
            ]
        ], $before);
    }
}
