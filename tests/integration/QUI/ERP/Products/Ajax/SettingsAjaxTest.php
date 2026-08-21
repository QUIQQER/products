<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI\ERP\Products\Handler\Fields;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class SettingsAjaxTest extends AjaxTestCase
{
    private string|false $originalExecutionTime;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalExecutionTime = ini_get('max_execution_time');
        set_time_limit(0);
    }

    protected function tearDown(): void
    {
        if ($this->originalExecutionTime !== false) {
            set_time_limit((int)$this->originalExecutionTime);
        }

        parent::tearDown();
    }

    public function testSystemCheckTreatsUnlimitedExecutionTimeAsSufficient(): void
    {
        self::assertSame('0', ini_get('max_execution_time'));

        $all = $this->invokeEndpoint(
            'settings/checkSystem.php',
            'package_quiqqer_products_ajax_settings_checkSystem'
        );
        self::assertTrue($all['timeSufficient']);
        self::assertStringEndsWith('./console products:update-prices', $all['commands']['all']);
        self::assertStringEndsWith('./console products:update-prices --activeOnly', $all['commands']['active']);

        $categoryId = ProductTestHelper::getCategory()->getId();
        ProductTestHelper::createProduct('settings-category-product');
        $category = $this->invokeEndpoint(
            'settings/checkSystem.php',
            'package_quiqqer_products_ajax_settings_checkSystem',
            $categoryId
        );
        self::assertTrue($category['timeSufficient']);
        self::assertStringEndsWith(
            './console products:update-prices --categoryId=' . $categoryId,
            $category['commands']['all']
        );
        self::assertStringEndsWith(
            './console products:update-prices --activeOnly --categoryId=' . $categoryId,
            $category['commands']['active']
        );
    }

    public function testPriceFieldSettingsExposePersistedPriceFieldsInIdOrder(): void
    {
        $fields = $this->invokeEndpointAsAdmin(
            'settings/getPriceFields.php',
            'package_quiqqer_products_ajax_settings_getPriceFields'
        );

        self::assertNotEmpty($fields);
        $ids = array_map('intval', array_column($fields, 'id'));
        $sorted = $ids;
        sort($sorted);
        self::assertSame($sorted, $ids);
        self::assertContains(Fields::FIELD_PRICE, $ids);
        self::assertSame([true], array_values(array_unique(array_column($fields, 'edit'))));
    }

    public function testVatArticleNumberAndPackageSettingsReturnRuntimeConfiguration(): void
    {
        $vatEntries = $this->invokeEndpointAsAdmin(
            'settings/getVatEntries.php',
            'package_quiqqer_products_ajax_settings_getVatEntries'
        );
        self::assertNotEmpty($vatEntries);

        $autoGenerate = $this->invokeEndpointAsAdmin(
            'settings/isAutoGenerateNextArticleNo.php',
            'package_quiqqer_products_ajax_settings_isAutoGenerateNextArticleNo'
        );
        self::assertIsBool($autoGenerate);

        $packages = $this->invokeEndpointAsAdmin(
            'getInstalledProductPackages.php',
            'package_quiqqer_products_ajax_getInstalledProductPackages'
        );
        self::assertSame(
            ['quiqqer/productstags', 'quiqqer/productsimportexport'],
            array_keys($packages)
        );
        self::assertContainsOnly('bool', $packages);
    }
}
