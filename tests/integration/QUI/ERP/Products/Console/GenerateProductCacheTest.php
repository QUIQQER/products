<?php

namespace QUITests\ERP\Products\Integration\Console;

use QUI;
use QUI\ERP\Products\Console\GenerateProductCache;
use QUI\ERP\Products\Handler\Cache;
use QUI\ERP\Products\Handler\Products;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class GenerateProductCacheTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testRebuildProcessesActiveProductsAndRestoresLockState(): void
    {
        $Active = ProductTestHelper::createProduct('cache-tool-active', 21.0);
        $Inactive = ProductTestHelper::createProduct('cache-tool-inactive', 11.0);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Active): void {
            $Active->activate($SystemUser);
        });
        $Package = QUI::getPackage('quiqqer/products');
        $lockKey = 'products-generating';
        $originalFrontendCache = Products::$createFrontendCache;
        $originalLockState = QUI\Lock\Locker::isLocked($Package, $lockKey, null, false);
        QUI\Lock\Locker::unlock($Package, $lockKey);

        try {
            $Tool = new GenerateProductCache();
            self::assertSame('products:generate-product-cache', $Tool->getName());
            self::assertSame(
                ['unlock', 'rebuild', 'withControlCache'],
                array_keys($Tool->getArgumentDefinitions())
            );
            $Tool->setArgument('unlock', true);
            $Tool->setArgument('rebuild', true);
            $Tool->setArgument('withControlCache', false);
            $Tool->execute();

            self::assertFalse(QUI\Lock\Locker::isLocked($Package, $lockKey));
            self::assertFalse(Products::$createFrontendCache);
            self::assertSame(21.0, Products::getNewProductInstance($Active->getId())->getPrice()->value());
            self::assertFalse(Products::getNewProductInstance($Inactive->getId())->isActive());
        } finally {
            QUI\Lock\Locker::unlock($Package, $lockKey);

            if ($originalLockState !== false) {
                QUI\Lock\Locker::lock($Package, $lockKey);
            }

            Products::$createFrontendCache = $originalFrontendCache;
        }
    }

    public function testExistingProductCacheIsPreservedWithoutRebuildFlag(): void
    {
        $Product = ProductTestHelper::createProduct('cache-tool-existing', 17.0);
        $Package = QUI::getPackage('quiqqer/products');
        $lockKey = 'products-generating';
        $cacheName = Cache::getProductCachePath($Product->getId());
        $originalFrontendCache = Products::$createFrontendCache;
        $originalLockState = QUI\Lock\Locker::isLocked($Package, $lockKey, null, false);
        QUI\Lock\Locker::unlock($Package, $lockKey);
        QUI\Cache\LongTermCache::set($cacheName, ['marker' => 'preserved']);

        try {
            $Tool = new GenerateProductCache();
            $Tool->setArgument('unlock', true);
            $Tool->setArgument('rebuild', false);
            $Tool->setArgument('withControlCache', false);
            $Tool->execute();

            self::assertSame(['marker' => 'preserved'], QUI\Cache\LongTermCache::get($cacheName));
            self::assertFalse(QUI\Lock\Locker::isLocked($Package, $lockKey));
        } finally {
            QUI\Lock\Locker::unlock($Package, $lockKey);

            if ($originalLockState !== false) {
                QUI\Lock\Locker::lock($Package, $lockKey);
            }

            QUI\Cache\LongTermCache::clear($cacheName);
            Products::$createFrontendCache = $originalFrontendCache;
        }
    }
}
