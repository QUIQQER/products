<?php

namespace QUITests\ERP\Products\Integration\Category;

use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class CategoryLifecycleTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testCategoryHierarchyFieldsAndCustomDataArePersisted(): void
    {
        $Parent = $this->createFixtureCategory(0, 'parent');
        $Child = $this->createFixtureCategory($Parent->getId(), 'child');

        try {
            self::assertSame($Parent->getId(), $Child->getParentId());
            self::assertSame($Parent->getId(), $Child->getParent()->getId());
            self::assertSame(
                [$Child->getId()],
                array_map(static fn ($Category): int => $Category->getId(), $Parent->getChildren())
            );
            self::assertSame(1, $Parent->countChildren());
            self::assertTrue(Categories::existsCategory($Child->getId()));
            self::assertTrue(Categories::isCategory($Child));
            self::assertFalse(Categories::isCategory(new \stdClass()));

            $Field = $this->findNonStandardField();
            $Field->setAttribute('publicStatus', 1);
            $Field->setAttribute('searchStatus', 1);
            $Child->addField($Field);
            $Child->addField($Field);
            $Child->setCustomDataEntry('channel', 'phpunit');
            $Child->setCustomDataEntry('limits', ['minimum' => 2]);
            $Child->setCustomDataEntry('ignored', null);

            ProductTestHelper::runAsSystemUser(static function () use ($Child): void {
                $Child->save();
            });

            $row = QUI::getDataBaseConnection()->fetchAssociative(
                'SELECT fields, parentId, custom_data FROM '
                . QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName())
                . ' WHERE id = ?',
                [$Child->getId()]
            );

            self::assertIsArray($row);
            self::assertSame($Parent->getId(), (int)$row['parentId']);
            self::assertSame('phpunit', $Child->getCustomDataEntry('channel'));
            self::assertSame(['minimum' => 2], $Child->getCustomDataEntry('limits'));
            self::assertNull($Child->getCustomDataEntry('missing'));

            $persistedFields = json_decode((string)$row['fields'], true, 512, JSON_THROW_ON_ERROR);
            self::assertCount(1, $persistedFields);
            self::assertSame($Field->getId(), (int)$persistedFields[0]['id']);
            self::assertSame(1, (int)$persistedFields[0]['publicStatus']);
            self::assertSame(1, (int)$persistedFields[0]['searchStatus']);
            self::assertSame($Field->getId(), $Child->getField($Field->getId())?->getId());
            self::assertContains($Field->getId(), array_map(
                static fn (Field $Entry): int => $Entry->getId(),
                $Child->getSearchFields()
            ));

            $attributes = $Child->getAttributes();
            self::assertSame($Child->getId(), $attributes['id']);
            self::assertSame($Parent->getId(), $attributes['parent']);
            self::assertSame('phpunit', $attributes['custom_data']['channel']);
        } finally {
            $this->deleteFixtureCategory($Parent);
        }
    }

    public function testCategoryReturnsItsPersistedProductsAndSiteBinding(): void
    {
        $Category = ProductTestHelper::getCategory();
        $Product = ProductTestHelper::createProduct('category-membership');
        $productId = $Product->getId();

        self::assertSame([$productId], array_map(
            static fn ($Entry): int => $Entry->getId(),
            $Category->getProducts(['where' => ['id' => $productId]])
        ));
        self::assertSame([$productId], array_map(
            'intval',
            $Category->getProductIds(['where' => ['id' => $productId]])
        ));
        self::assertSame(1, $Category->countProducts(['where' => ['id' => $productId]]));

        $sites = $Category->getSites(ProductTestHelper::getProject());
        self::assertNotEmpty($sites);
        self::assertSame(ProductTestHelper::getCategorySite()->getId(), $Category->getSite(
            ProductTestHelper::getProject()
        )->getId());
        self::assertNotSame('', $Category->getUrl(ProductTestHelper::getProject()));
    }

    public function testDeletedCategoryIsNoLongerReturnedFromRuntimeCache(): void
    {
        $Category = $this->createFixtureCategory(0, 'delete-cache');
        $categoryId = $Category->getId();

        $this->deleteFixtureCategory($Category);

        self::assertSame(0, (int)QUI::getDataBaseConnection()->fetchOne(
            'SELECT COUNT(*) FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName())
            . ' WHERE id = ?',
            [$categoryId]
        ));
        self::assertArrayNotHasKey(
            $categoryId,
            (new ReflectionProperty(Categories::class, 'list'))->getValue()
        );
        self::assertFalse(Categories::existsCategory($categoryId));
    }

    private function createFixtureCategory(int $parentId, string $label): Category
    {
        return ProductTestHelper::runAsSystemUser(static function () use ($parentId, $label): Category {
            $Category = Categories::createCategory(
                $parentId,
                ProductTestHelper::PREFIX . $label . '-' . bin2hex(random_bytes(5))
            );
            self::assertInstanceOf(Category::class, $Category);
            $Category->setCustomDataEntry('phpunitFixture', ProductTestHelper::PREFIX);
            $Category->save();

            return $Category;
        });
    }

    private function deleteFixtureCategory(Category $Category): void
    {
        ProductTestHelper::runAsSystemUser(static function () use ($Category): void {
            $Category->delete();
        });
    }

    private function findNonStandardField(): Field
    {
        foreach (Fields::getFields() as $Field) {
            if (!$Field->isStandard() && !$Field->isSystem()) {
                return clone $Field;
            }
        }

        self::fail('The complete field fixture must contain a non-standard field.');
    }
}
