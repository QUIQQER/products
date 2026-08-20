<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\TestCase;

abstract class ProductIntegrationTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ProductTestHelper::assertDatabaseIsAvailable();
        ProductTestHelper::initialize();
    }

    protected function setUp(): void
    {
        ProductTestHelper::cleanupProducts();
        ProductTestHelper::cleanupCustomFields();
    }

    protected function tearDown(): void
    {
        ProductTestHelper::cleanupProducts();
        ProductTestHelper::cleanupCustomFields();
    }

    public static function tearDownAfterClass(): void
    {
        ProductTestHelper::cleanupAll();
    }
}
