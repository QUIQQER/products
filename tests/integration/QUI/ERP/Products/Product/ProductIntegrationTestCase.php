<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\TestCase;
use Throwable;

abstract class ProductIntegrationTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        try {
            ProductTestHelper::assertDatabaseIsAvailable();
        } catch (Throwable $Exception) {
            self::markTestSkipped($Exception->getMessage());
        }

        ProductTestHelper::initialize();
    }

    protected function setUp(): void
    {
        ProductTestHelper::cleanupProducts();
    }

    protected function tearDown(): void
    {
        ProductTestHelper::cleanupProducts();
    }

    public static function tearDownAfterClass(): void
    {
        ProductTestHelper::cleanupAll();
    }
}
