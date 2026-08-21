<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ControlAjaxTest extends AjaxTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testCategoryProductListRendersActiveProductsAndSupportsPagination(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-category-product-list', 28.5);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Project = ProductTestHelper::getProject();
        $projectData = json_encode([
            'name' => $Project->getName(),
            'lang' => $Project->getLang()
        ], JSON_THROW_ON_ERROR);
        $searchParams = json_encode([], JSON_THROW_ON_ERROR);
        $endpoint = 'package_quiqqer_products_ajax_controls_categories_productList';

        $start = $this->invokeEndpoint(
            'controls/categories/productList.php',
            $endpoint,
            $projectData,
            ProductTestHelper::getCategorySite()->getId(),
            ProductTestHelper::getCategory()->getId(),
            10,
            'list',
            $searchParams,
            false,
            0
        );

        self::assertSame(1, $start['count']);
        self::assertFalse($start['more']);
        self::assertStringContainsString('ajax-category-product-list', $start['html']);
        self::assertStringContainsString('data-pid="' . $Product->getId() . '"', $start['html']);

        $next = $this->invokeEndpoint(
            'controls/categories/productList.php',
            $endpoint,
            $projectData,
            ProductTestHelper::getCategorySite()->getId(),
            999999,
            10,
            'list',
            $searchParams,
            true,
            10
        );

        self::assertSame(1, $next['count']);
        self::assertFalse($next['more']);
        self::assertStringNotContainsString('ajax-category-product-list', $next['html']);
    }
}
