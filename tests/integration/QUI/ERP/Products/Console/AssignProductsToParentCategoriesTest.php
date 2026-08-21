<?php

namespace QUITests\ERP\Products\Integration\Console;

use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Console\AssignProductsToParentCategories;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Products;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class AssignProductsToParentCategoriesTest extends ProductIntegrationTestCase
{
    public function testExplicitProductGetsEveryParentCategoryPersisted(): void
    {
        $Parent = $this->createCategory(0, 'console-parent');
        $Child = $this->createCategory($Parent->getId(), 'console-child');
        $Grandchild = $this->createCategory($Child->getId(), 'console-grandchild');
        $Product = ProductTestHelper::createProduct('console-category-product', 13.0);
        $Product->clearCategories();
        $Product->addCategory($Grandchild);
        $Product->setMainCategory($Grandchild);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->save($SystemUser);
        });
        Products::cleanProductInstanceMemCache($Product->getId());
        $initialCategoryIds = array_map(
            static fn ($Category): int => $Category->getId(),
            Products::getNewProductInstance($Product->getId())->getCategories()
        );
        sort($initialCategoryIds);
        $expectedInitialCategoryIds = [0, $Grandchild->getId()];
        sort($expectedInitialCategoryIds);
        self::assertSame($expectedInitialCategoryIds, $initialCategoryIds);

        $Tool = new AssignProductsToParentCategories();
        self::assertSame('products:assign-parent-categories', $Tool->getName());
        $Tool->setArgument('productIds', $Product->getId() . ',999999');
        $Tool->execute();

        Products::cleanProductInstanceMemCache($Product->getId());
        $Reloaded = Products::getNewProductInstance($Product->getId());
        $categoryIds = array_map(
            static fn ($Category): int => $Category->getId(),
            $Reloaded->getCategories()
        );
        sort($categoryIds);
        $expected = [0, $Parent->getId(), $Child->getId(), $Grandchild->getId()];
        sort($expected);
        self::assertSame($expected, $categoryIds);
        self::assertSame($Grandchild->getId(), $Reloaded->getCategory()?->getId());
    }

    public function testMissingProductArgumentProcessesAllExistingProducts(): void
    {
        $Product = ProductTestHelper::createProduct('console-all-products', 7.0);

        (new AssignProductsToParentCategories())->execute();

        Products::cleanProductInstanceMemCache($Product->getId());
        self::assertContains(
            ProductTestHelper::getCategory()->getId(),
            array_map(
                static fn ($Category): int => $Category->getId(),
                Products::getNewProductInstance($Product->getId())->getCategories()
            )
        );
    }

    private function createCategory(int $parentId, string $label): Category
    {
        return ProductTestHelper::runAsSystemUser(static function () use ($parentId, $label): Category {
            $Category = Categories::createCategory(
                $parentId,
                ProductTestHelper::PREFIX . $label
            );
            self::assertInstanceOf(Category::class, $Category);
            $Category->setCustomDataEntry('phpunitFixture', ProductTestHelper::PREFIX);
            $Category->save();

            return $Category;
        });
    }
}
