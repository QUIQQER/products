<?php

namespace QUITests\ERP\Products\Integration\Handler;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\NumberRange;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductsHandlerTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testConfiguredVariantFieldsReturnOnlyActiveExistingFields(): void
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $originalEditable = $Config->getSection('editableFields');
        $originalInherited = $Config->getSection('inheritedFields');

        try {
            $Config->setSection('editableFields', [
                Fields::FIELD_PRICE => 1,
                Fields::FIELD_TITLE => 0,
                990001 => 1
            ]);
            $Config->setSection('inheritedFields', [
                Fields::FIELD_TITLE => 1,
                Fields::FIELD_PRICE => 0,
                990002 => 1
            ]);

            self::assertSame(
                [Fields::FIELD_PRICE],
                array_map(
                    static fn ($Field): int => $Field->getId(),
                    Products::getGlobalEditableVariantFields()
                )
            );
            self::assertSame(
                [Fields::FIELD_TITLE],
                array_map(
                    static fn ($Field): int => $Field->getId(),
                    Products::getGlobalInheritedVariantFields()
                )
            );
        } finally {
            $Config->setSection(
                'editableFields',
                is_array($originalEditable) ? $originalEditable : []
            );
            $Config->setSection(
                'inheritedFields',
                is_array($originalInherited) ? $originalInherited : []
            );
        }
    }

    public function testRuntimeSwitchesCanBeChangedAndRestored(): void
    {
        $original = [
            'write' => Products::$writeProductDataToDb,
            'events' => Products::$fireEventsOnProductSave,
            'search' => Products::$updateProductSearchCache,
            'unique' => Products::$useRuntimeCacheForUniqueProducts
        ];

        try {
            Products::disableGlobalWriteProductDataToDb();
            Products::disableGlobalFireEventsOnProductSave();
            Products::disableGlobalProductSearchCacheUpdate();
            Products::disableRuntimeCacheForUniqueProducts();

            self::assertFalse(Products::$writeProductDataToDb);
            self::assertFalse(Products::$fireEventsOnProductSave);
            self::assertFalse(Products::$updateProductSearchCache);
            self::assertFalse(Products::$useRuntimeCacheForUniqueProducts);

            Products::enableGlobalWriteProductDataToDb();
            Products::enableGlobalFireEventsOnProductSave();
            Products::enableGlobalProductSearchCacheUpdate();
            Products::enableRuntimeCacheForUniqueProducts();

            self::assertTrue(Products::$writeProductDataToDb);
            self::assertTrue(Products::$fireEventsOnProductSave);
            self::assertTrue(Products::$updateProductSearchCache);
            self::assertTrue(Products::$useRuntimeCacheForUniqueProducts);
        } finally {
            Products::$writeProductDataToDb = $original['write'];
            Products::$fireEventsOnProductSave = $original['events'];
            Products::$updateProductSearchCache = $original['search'];
            Products::$useRuntimeCacheForUniqueProducts = $original['unique'];
        }
    }

    public function testArticleNumberUsesConfiguredPlaceholdersAndSqliteRange(): void
    {
        $Product = ProductTestHelper::createProduct('article-number-handler', 12.5);
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $original = $Config->getSection('autoArticleNos');
        $nextId = (new NumberRange())->getRange();

        try {
            $Config->setSection('autoArticleNos', [
                'prefix' => 'UT-#YEAR-#MONTH-#DAY-#CAT_ID-',
                'suffix' => '-END'
            ]);

            self::assertSame(
                'UT-' . date('Y-m-d') . '-'
                . ProductTestHelper::getCategory()->getId() . '-'
                . $nextId . '-END',
                Products::generateArticleNo($Product)
            );
        } finally {
            $Config->setSection(
                'autoArticleNos',
                is_array($original) ? $original : []
            );
        }
    }

    public function testCleanupRemovesOrphanCacheAndPreservesExistingProduct(): void
    {
        $Survivor = ProductTestHelper::createProduct('cleanup-survivor', 21);
        $Orphan = ProductTestHelper::createProduct('cleanup-orphan', 22);
        $Connection = QUI::getDataBaseConnection();
        $productTable = QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName());
        $cacheTable = QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductCacheTableName());
        $originalWatcherState = class_exists(QUI\Watcher::class)
            ? QUI\Watcher::$globalWatcherDisable
            : null;

        $Connection->delete($productTable, ['id' => $Orphan->getId()]);
        Products::cleanProductInstanceMemCache($Orphan->getId());

        self::assertSame(
            0,
            (int)$Connection->fetchOne(
                'SELECT COUNT(*) FROM ' . $productTable . ' WHERE id = ?',
                [$Orphan->getId()]
            )
        );
        self::assertFalse(Products::existsProduct($Orphan->getId()));

        self::assertGreaterThan(
            0,
            (int)$Connection->fetchOne(
                'SELECT COUNT(*) FROM ' . $cacheTable . ' WHERE id = ?',
                [$Orphan->getId()]
            )
        );

        try {
            Products::cleanup();
        } finally {
            if ($originalWatcherState !== null) {
                QUI\Watcher::$globalWatcherDisable = $originalWatcherState;
            }
        }

        self::assertTrue(Products::existsProduct($Survivor->getId()));
        self::assertSame(
            'cleanup-survivor',
            Products::getNewProductInstance($Survivor->getId())->getTitle()
        );
        self::assertFalse(Products::existsProduct($Orphan->getId()));
        self::assertSame(
            0,
            (int)$Connection->fetchOne(
                'SELECT COUNT(*) FROM ' . $cacheTable . ' WHERE id = ?',
                [$Orphan->getId()]
            )
        );

        try {
            Products::getProduct($Orphan->getId());
            self::fail('An orphan product must not be restored from its runtime cache.');
        } catch (QUI\ERP\Products\Product\Exception $Exception) {
            self::assertSame(404, $Exception->getCode());
        }
    }
}
