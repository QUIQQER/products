<?php

namespace QUITests\ERP\Products\Integration\Category;

use QUI;
use QUI\ERP\Products\Category\AllProducts;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class AllProductsTest extends ProductIntegrationTestCase
{
    public function testVirtualCategoryIdentityCannotBePersistedDeletedOrReparented(): void
    {
        $categoryCount = $this->countPersistedCategories();
        $AllProducts = new AllProducts(9876, ['parentId' => 1234]);

        self::assertSame(0, $AllProducts->getId());
        self::assertFalse($AllProducts->getParentId());
        self::assertFalse($AllProducts->getParent());

        $AllProducts->setParentId(ProductTestHelper::getCategory()->getId());
        $AllProducts->save(QUI::getUsers()->getSystemUser());
        $AllProducts->delete(QUI::getUsers()->getSystemUser());

        self::assertFalse($AllProducts->getParentId());
        self::assertFalse($AllProducts->getParent());
        self::assertSame($categoryCount, $this->countPersistedCategories());
        self::assertSame(0, (int)QUI::getDataBaseConnection()->fetchOne(
            'SELECT COUNT(*) FROM '
            . QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName())
            . ' WHERE id = 0'
        ));
    }

    public function testProductQueriesApplyFiltersLimitsAndOrderingAcrossAllCategories(): void
    {
        $First = ProductTestHelper::createProduct('all-products-first', 10.0);
        $Second = ProductTestHelper::createProduct('all-products-second', 20.0);
        $Third = ProductTestHelper::createProduct('all-products-third', 30.0);
        $ids = [$First->getId(), $Second->getId(), $Third->getId()];
        $AllProducts = new AllProducts();
        $where = [
            'id' => [
                'type' => 'IN',
                'value' => $ids
            ]
        ];

        self::assertSame(
            [$Third->getId(), $Second->getId()],
            array_map('intval', $AllProducts->getProductIds([
                'where' => $where,
                'limit' => 2,
                'order' => 'id DESC',
                'debug' => false
            ]))
        );

        $products = $AllProducts->getProducts([
            'where' => $where,
            'limit' => 1,
            'order' => 'id ASC',
            'debug' => false
        ]);

        self::assertCount(1, $products);
        self::assertSame($First->getId(), $products[0]->getId());
        self::assertSame(3, $AllProducts->countProducts([
            'where' => $where,
            'debug' => false
        ]));

        $missingWhere = ['id' => max($ids) + 100000];

        self::assertSame([], $AllProducts->getProductIds(['where' => $missingWhere]));
        self::assertSame([], $AllProducts->getProducts(['where' => $missingWhere]));
        self::assertSame(0, $AllProducts->countProducts(['where' => $missingWhere]));
    }

    public function testFieldsExposeTheConfiguredFrontendSearchFields(): void
    {
        $fields = (new AllProducts())->getFields();

        self::assertNotEmpty($fields);
        self::assertContainsOnlyInstancesOf(Field::class, $fields);
        self::assertSame(
            array_values(array_unique(array_map(static fn (Field $Field): int => $Field->getId(), $fields))),
            array_map(static fn (Field $Field): int => $Field->getId(), $fields)
        );
    }

    private function countPersistedCategories(): int
    {
        return (int)QUI::getDataBaseConnection()->fetchOne(
            'SELECT COUNT(*) FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName())
        );
    }
}
