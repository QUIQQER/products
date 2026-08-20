<?php

namespace QUITests\ERP\Products\Integration\Category;

use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Category\ViewBackend;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Products;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ViewBackendTest extends ProductIntegrationTestCase
{
    public function testHierarchyDelegationAndProductQueriesExposeOnlyActiveProducts(): void
    {
        $Category = $this->createCategory(0, 'backend-view-parent');
        $Child = $this->createCategory($Category->getId(), 'backend-view-child');
        $Active = ProductTestHelper::createProduct('backend-view-active', 12.0);
        $Inactive = ProductTestHelper::createProduct('backend-view-inactive', 8.0);

        foreach ([$Active, $Inactive] as $Product) {
            $Product->addCategory($Category);
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                $Product->save($SystemUser);
            });
        }
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Active): void {
            $Active->activate($SystemUser);
        });
        Products::cleanProductInstanceMemCache();

        $View = new ViewBackend(Categories::getCategory($Category->getId()));

        self::assertSame($Category->getId(), $View->getId());
        self::assertSame(0, $View->getParentId());
        self::assertSame(0, $View->getParent()->getId());
        self::assertSame(1, $View->countChildren());
        self::assertSame([$Child->getId()], array_map(
            static fn ($Category): int => $Category->getId(),
            $View->getChildren()
        ));
        self::assertSame(1, $View->countProducts());
        self::assertSame([$Active->getId()], array_map('intval', $View->getProductIds()));
        self::assertSame([$Active->getId()], array_map(
            static fn ($Product): int => $Product->getId(),
            $View->getProducts()
        ));
        self::assertSame($Category->getAttributes(), $View->getAttributes());
        self::assertSame($Category->getTitle(), $View->getTitle());
        self::assertSame($Category->getDescription(), $View->getDescription());
        $fieldIds = static fn (array $fields): array => array_map(
            static fn ($Field): int => $Field->getId(),
            $fields
        );

        self::assertNotEmpty($Category->getFields());
        self::assertSame($fieldIds($Category->getFields()), $fieldIds($View->getFields()));
        self::assertSame(
            $Category->getFields()[0]->getId(),
            $View->getField($Category->getFields()[0]->getId())->getId()
        );
        self::assertNull($View->getField(999999));
        self::assertSame($fieldIds($Category->getSearchFields()), $fieldIds($View->getSearchFields()));
    }

    public function testBoundCategorySiteAndUrlAreDelegatedForProject(): void
    {
        $Category = ProductTestHelper::getCategory();
        $Project = ProductTestHelper::getProject();
        $View = new ViewBackend($Category);

        self::assertSame($Category->getSite($Project)->getId(), $View->getSite($Project)->getId());
        self::assertSame(
            array_map(static fn ($Site): int => $Site->getId(), $Category->getSites($Project)),
            array_map(static fn ($Site): int => $Site->getId(), $View->getSites($Project))
        );
        self::assertSame($Category->getUrl($Project), $View->getUrl($Project));
    }

    private function createCategory(int $parentId, string $label): Category
    {
        return ProductTestHelper::runAsSystemUser(static function () use ($parentId, $label): Category {
            $Category = Categories::createCategory($parentId, ProductTestHelper::PREFIX . $label);
            self::assertInstanceOf(Category::class, $Category);
            $Category->setCustomDataEntry('phpunitFixture', ProductTestHelper::PREFIX);
            $Category->save();

            return $Category;
        });
    }
}
