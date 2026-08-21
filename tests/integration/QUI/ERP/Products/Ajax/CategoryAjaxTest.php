<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Category\AllProducts;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class CategoryAjaxTest extends AjaxTestCase
{
    public function testCategoryEndpointsManageHierarchyAndReturnCounts(): void
    {
        $Parent = $this->createCategory('ajax-parent', 0);
        $Child = $this->createCategory('ajax-child', $Parent->getId());

        $data = $this->invokeEndpoint(
            'categories/get.php',
            'package_quiqqer_products_ajax_categories_get',
            $Child->getId()
        );
        self::assertSame($Child->getId(), $data['id']);
        self::assertSame($Parent->getId(), $data['parent']);

        $children = $this->invokeEndpoint(
            'categories/getChildren.php',
            'package_quiqqer_products_ajax_categories_getChildren',
            $Parent->getId(),
            json_encode(['countChildren' => true], JSON_THROW_ON_ERROR)
        );
        self::assertCount(1, $children);
        self::assertSame($Child->getId(), $children[0]['id']);
        self::assertSame(0, $children[0]['countChildren']);

        self::assertSame(
            [0, $Parent->getId(), $Child->getId()],
            $this->invokeEndpoint(
                'categories/path.php',
                'package_quiqqer_products_ajax_categories_path',
                $Child->getId()
            )
        );

        $information = $this->invokeEndpoint(
            'categories/getInformation.php',
            'package_quiqqer_products_ajax_categories_getInformation',
            $Parent->getId()
        );
        self::assertSame(1, $information['categories']);
        self::assertSame(0, $information['products']);
        self::assertGreaterThan(0, $information['fields']);

        $this->invokeEndpoint(
            'categories/setParent.php',
            'package_quiqqer_products_ajax_categories_setParent',
            $Child->getId(),
            0
        );
        self::assertSame(0, Categories::getCategory($Child->getId())->getParentId());

        $listed = $this->invokeEndpoint(
            'categories/getCategories.php',
            'package_quiqqer_products_ajax_categories_getCategories',
            json_encode([$Parent->getId(), $Child->getId(), 999999], JSON_THROW_ON_ERROR)
        );
        self::assertSame(
            [$Parent->getId(), $Child->getId()],
            array_column($listed, 'id')
        );

        $this->invokeEndpoint(
            'categories/deleteChildren.php',
            'package_quiqqer_products_ajax_categories_deleteChildren',
            json_encode([$Parent->getId(), $Child->getId()], JSON_THROW_ON_ERROR)
        );
        self::assertFalse(Categories::existsCategory($Parent->getId()));
        self::assertFalse(Categories::existsCategory($Child->getId()));
    }

    public function testCategoryProductAssignmentAndGridReflectPersistedChanges(): void
    {
        $Category = $this->createCategory('ajax-products', 0);
        $Product = ProductTestHelper::createProduct('ajax-category-product', 15.0);
        $productId = $Product->getId();

        $this->invokeEndpoint(
            'categories/addProducts.php',
            'package_quiqqer_products_ajax_categories_addProducts',
            $Category->getId(),
            json_encode([$productId], JSON_THROW_ON_ERROR)
        );
        Products::cleanProductInstanceMemCache($productId);
        self::assertContains($Category->getId(), array_map(
            static fn ($Entry): int => $Entry->getId(),
            Products::getNewProductInstance($productId)->getCategories()
        ));

        $grid = $this->invokeEndpoint(
            'categories/getProductsFromCategory.php',
            'package_quiqqer_products_ajax_categories_getProductsFromCategory',
            $Category->getId(),
            json_encode(['perPage' => 10, 'page' => 1], JSON_THROW_ON_ERROR)
        );
        self::assertSame(1, $grid['total']);
        self::assertSame($productId, (int)$grid['data'][0]['id']);
        self::assertSame('ajax-category-product', $grid['data'][0]['title']);
        self::assertSame(15.0, (float)$grid['data'][0]['price']);

        $this->invokeEndpoint(
            'categories/update.php',
            'package_quiqqer_products_ajax_categories_update',
            $Category->getId(),
            json_encode([
                'custom_data' => [
                    'priceFieldFactors' => ['factor' => 1.2],
                    'notAllowed' => 'ignored'
                ]
            ], JSON_THROW_ON_ERROR),
            false
        );
        $ReloadedCategory = Categories::getCategory($Category->getId());
        self::assertSame(['factor' => 1.2], $ReloadedCategory->getCustomDataEntry('priceFieldFactors'));
        self::assertNull($ReloadedCategory->getCustomDataEntry('notAllowed'));

        $this->invokeEndpoint(
            'categories/removeProducts.php',
            'package_quiqqer_products_ajax_categories_removeProducts',
            $Category->getId(),
            json_encode([$productId], JSON_THROW_ON_ERROR)
        );
        Products::cleanProductInstanceMemCache($productId);
        self::assertNotContains($Category->getId(), array_map(
            static fn ($Entry): int => $Entry->getId(),
            Products::getNewProductInstance($productId)->getCategories()
        ));

        $this->invokeEndpoint(
            'categories/deleteChild.php',
            'package_quiqqer_products_ajax_categories_deleteChild',
            $Category->getId()
        );
        self::assertFalse(Categories::existsCategory($Category->getId()));
    }

    public function testCategoryListReturnsFilteredCategoryAndFactorInformation(): void
    {
        $Category = $this->createCategory('ajax-list', 0);
        $Category->setCustomDataEntry('priceFieldFactors', [
            Fields::FIELD_PRICE => 1.25,
            'categoryPriority' => 7
        ]);
        $Category->save();

        $OriginalLocale = QUI::$Locale;
        $Locale = $this->createMock(\QUI\Locale::class);
        $Locale->method('get')->willReturnCallback(
            static function (string $group, false | string $value, false | array $replace = false): string {
                if ($value === 'categories.list.priceFieldFactorFields') {
                    return $replace['fields'] . '|' . $replace['priority'];
                }

                return 'translated:' . $value;
            }
        );
        QUI::$Locale = $Locale;

        try {
            $result = $this->invokeEndpointAsAdmin(
                'categories/list.php',
                'package_quiqqer_products_ajax_categories_list',
                json_encode([
                    'page' => 1,
                    'perPage' => 10,
                    'where' => ['id' => $Category->getId()]
                ], JSON_THROW_ON_ERROR)
            );
        } finally {
            QUI::$Locale = $OriginalLocale;
        }

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['data']);
        self::assertSame($Category->getId(), (int)$result['data'][0]['id']);
        self::assertSame(
            'translated:products.category.' . $Category->getId() . '.title',
            $result['data'][0]['title']
        );
        self::assertSame(
            'translated:products.field.' . Fields::FIELD_PRICE . '.title|7',
            $result['data'][0]['priceFieldFactorFields']
        );
    }

    public function testCategorySearchFindsMatchingCategoriesAndAllProductsEntry(): void
    {
        $Alpha = $this->createCategory('ajax-search-alpha', 0);
        $Zulu = $this->createCategory('ajax-search-zulu', 0);

        $result = $this->invokeEndpointAsAdmin(
            'categories/search.php',
            'package_quiqqer_products_ajax_categories_search',
            json_encode(['limit' => 10], JSON_THROW_ON_ERROR),
            json_encode(['fields' => 'ajax-search'], JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            [$Alpha->getId(), $Zulu->getId()],
            array_map('intval', array_column($result, 'id'))
        );
        self::assertSame([$Alpha->getTitle(), $Zulu->getTitle()], array_column($result, 'title'));

        $AllProducts = new AllProducts();
        $allProductsResult = $this->invokeEndpointAsAdmin(
            'categories/search.php',
            'package_quiqqer_products_ajax_categories_search',
            json_encode(['limit' => 10], JSON_THROW_ON_ERROR),
            json_encode(['fields' => $AllProducts->getTitle()], JSON_THROW_ON_ERROR)
        );

        self::assertNotEmpty($allProductsResult);
        self::assertSame($AllProducts->getId(), (int)$allProductsResult[0]['id']);
        self::assertSame($AllProducts->getTitle(), $allProductsResult[0]['title']);
    }

    public function testCategoryFieldsAreDeduplicatedAndBoundSitesAreReturned(): void
    {
        $Category = $this->createCategory('ajax-fields', 0);
        $fieldResult = $this->invokeEndpointAsAdmin(
            'categories/getFields.php',
            'package_quiqqer_products_ajax_categories_getFields',
            json_encode([$Category->getId(), $Category->getId(), 999999], JSON_THROW_ON_ERROR)
        );
        $fieldIds = array_map('intval', array_column($fieldResult, 'id'));

        self::assertContains(Fields::FIELD_TITLE, $fieldIds);
        self::assertContains(Fields::FIELD_PRICE, $fieldIds);
        self::assertSame($fieldIds, array_values(array_unique($fieldIds)));

        $FixtureCategory = ProductTestHelper::getCategory();
        $Site = ProductTestHelper::getCategorySite();
        $siteResult = $this->invokeEndpointAsAdmin(
            'categories/getSites.php',
            'package_quiqqer_products_ajax_categories_getSites',
            $FixtureCategory->getId()
        );

        self::assertContains([
            'project' => $Site->getProject()->getName(),
            'lang' => $Site->getProject()->getLang(),
            'id' => $Site->getId()
        ], $siteResult);
    }

    public function testCategoryFieldsArePersistedToAllAssignedProducts(): void
    {
        $Category = $this->createCategory('ajax-propagate-fields', 0);
        $Product = ProductTestHelper::createProduct('ajax-field-propagation');
        $FieldList = new ReflectionProperty(Fields::class, 'list');
        $DeletedFieldIds = new ReflectionProperty(Fields::class, 'deletedFieldIds');
        $originalFieldList = $FieldList->getValue();
        $originalDeletedFieldIds = $DeletedFieldIds->getValue();
        $Field = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 2001,
            'name' => ProductTestHelper::PREFIX . 'ajax-propagated-field',
            'type' => Fields::TYPE_INPUT,
            'publicField' => 1
        ]));

        try {
            $this->invokeEndpoint(
                'categories/addProducts.php',
                'package_quiqqer_products_ajax_categories_addProducts',
                $Category->getId(),
                json_encode([$Product->getId()], JSON_THROW_ON_ERROR)
            );
            $Category->addField($Field);
            ProductTestHelper::runAsSystemUser(static function () use ($Category): void {
                $Category->save();
            });

            $fieldData = QUI::getDataBaseConnection()->fetchOne(
                'SELECT fieldData FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName())
                . ' WHERE id = ?',
                [$Product->getId()]
            );
            $persistedFieldIds = array_map(
                static fn (array $field): int => (int)$field['id'],
                json_decode((string)$fieldData, true, flags: JSON_THROW_ON_ERROR)
            );
            self::assertNotContains($Field->getId(), $persistedFieldIds);

            $originalMaxExecutionTime = ini_get('max_execution_time');
            $result = $this->invokeEndpointAsAdmin(
                'categories/setFieldsToAllProducts.php',
                'package_quiqqer_products_ajax_categories_setFieldsToAllProducts',
                $Category->getId()
            );

            self::assertNull($result);
            self::assertSame($originalMaxExecutionTime, ini_get('max_execution_time'));
            $fieldData = QUI::getDataBaseConnection()->fetchOne(
                'SELECT fieldData FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName())
                . ' WHERE id = ?',
                [$Product->getId()]
            );
            $persistedFieldIds = array_map(
                static fn (array $field): int => (int)$field['id'],
                json_decode((string)$fieldData, true, flags: JSON_THROW_ON_ERROR)
            );
            self::assertContains($Field->getId(), $persistedFieldIds);

            Products::cleanProductInstanceMemCache($Product->getId());
            $Reloaded = Products::getNewProductInstance($Product->getId());
            self::assertTrue($Reloaded->hasField($Field->getId()));
            self::assertNull($Reloaded->getFieldValue($Field->getId()));
        } finally {
            ProductTestHelper::runAsSystemUser(static function () use ($Field): void {
                $Field->delete();
            });
            $FieldList->setValue(null, $originalFieldList);
            $DeletedFieldIds->setValue(null, $originalDeletedFieldIds);
        }
    }

    private function createCategory(string $label, int $parentId): Category
    {
        $data = $this->invokeEndpoint(
            'categories/create.php',
            'package_quiqqer_products_ajax_categories_create',
            $parentId,
            json_encode(['title' => ProductTestHelper::PREFIX . $label], JSON_THROW_ON_ERROR)
        );
        $Category = Categories::getCategory((int)$data['id']);
        self::assertInstanceOf(Category::class, $Category);
        $Category->setCustomDataEntry('phpunitFixture', ProductTestHelper::PREFIX);
        $Category->save();
        QUI::getDataBaseConnection()->update(
            Tables::getCategoryTableName(),
            [
                'title_cache' => json_encode([
                    QUI::getLocale()->getCurrent() => ProductTestHelper::PREFIX . $label
                ], JSON_THROW_ON_ERROR)
            ],
            ['id' => $Category->getId()]
        );

        return Categories::getCategory($Category->getId());
    }
}
