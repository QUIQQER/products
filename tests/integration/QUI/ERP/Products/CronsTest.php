<?php

namespace QUITests\ERP\Products\Integration;

use QUI;
use QUI\ERP\Products\Crons;
use QUI\ERP\Products\Handler\Cache;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class CronsTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testUpdateProductCacheRebuildsDeletedDatabaseAndRuntimeEntries(): void
    {
        $Product = ProductTestHelper::createProduct('cron-cache-product', 27.5);
        $productId = $Product->getId();
        QUI::getDataBaseConnection()->delete(
            Tables::getProductCacheTableName(),
            ['id' => $productId]
        );
        QUI\Cache\LongTermCache::clear(Cache::getProductCachePath($productId) . '/db-data');
        $originalWatcherState = class_exists('\QUI\Watcher')
            ? QUI\Watcher::$globalWatcherDisable
            : null;
        $originalExecutionTime = ini_get('max_execution_time');

        try {
            Crons::updateProductCache();

            self::assertGreaterThan(
                0,
                (int)QUI::getDataBaseConnection()->fetchOne(
                    'SELECT COUNT(*) FROM '
                    . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductCacheTableName())
                    . ' WHERE id = ?',
                    [$productId]
                )
            );
            $dbData = QUI\Cache\LongTermCache::get(
                Cache::getProductCachePath($productId) . '/db-data'
            );
            self::assertSame($productId, (int)$dbData['id']);
        } finally {
            if ($originalWatcherState !== null) {
                QUI\Watcher::$globalWatcherDisable = $originalWatcherState;
            }

            if ($originalExecutionTime !== false) {
                set_time_limit((int)$originalExecutionTime);
            }
        }
    }

    public function testImageCacheCronPublishesProgressForEveryRegularProduct(): void
    {
        $Product = ProductTestHelper::createProduct('cron-image-product', 9.5);
        self::assertContains(
            $Product->getId(),
            array_map('intval', Products::getProductIds())
        );
        $beginEvents = [];
        $endEvents = [];
        $Begin = static function (int $id, int $current, int $count) use (&$beginEvents): void {
            $beginEvents[] = [$id, $current, $count];
        };
        $End = static function (int $id, int $current, int $count) use (&$endEvents): void {
            $endEvents[] = [$id, $current, $count];
        };
        QUI::getEvents()->addEvent('onGenerateCacheImagesOfProductsBegin', $Begin);
        QUI::getEvents()->addEvent('onGenerateCacheImagesOfProductsEnd', $End);
        $originalExecutionTime = ini_get('max_execution_time');

        try {
            Crons::generateCacheImagesOfProducts();
        } finally {
            QUI::getEvents()->removeEvent('onGenerateCacheImagesOfProductsBegin', $Begin);
            QUI::getEvents()->removeEvent('onGenerateCacheImagesOfProductsEnd', $End);
            if ($originalExecutionTime !== false) {
                set_time_limit((int)$originalExecutionTime);
            }
        }

        self::assertSame([[$Product->getId(), 0, 1]], $beginEvents);
        self::assertSame([[$Product->getId(), 0, 1]], $endEvents);
    }
}
