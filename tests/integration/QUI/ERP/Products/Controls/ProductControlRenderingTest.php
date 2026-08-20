<?php

namespace QUITests\ERP\Products\Integration\Controls;

use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductControlRenderingTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testActiveProductIsRenderedWithIdentityPriceAndDetails(): void
    {
        $Product = ProductTestHelper::createProduct('control-product', 19.95);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });

        $Control = new ProductControl([
            'Product' => $Product,
            'Project' => ProductTestHelper::getProject(),
            'Site' => ProductTestHelper::getCategorySite()
        ]);

        $html = $Control->create();

        self::assertStringContainsString('data-productid="' . $Product->getId() . '"', $html);
        self::assertStringContainsString('<h1>control-product</h1>', $html);
        self::assertStringContainsString('19,95', $html);
        self::assertStringContainsString('product-data-more-details', $html);
        self::assertTrue($Control->getAttribute('data-qui-option-show-price'));
        self::assertTrue($Control->getAttribute('data-qui-option-available'));
    }

    public function testRegularProductVariantSettingsReflectConfiguration(): void
    {
        $Product = ProductTestHelper::createProduct('control-settings', 7.5);
        $Control = new ProductControl([
            'Product' => $Product,
            'Project' => ProductTestHelper::getProject(),
            'Site' => ProductTestHelper::getCategorySite()
        ]);

        $settings = $Control->getVariantControlSettings();

        self::assertArrayHasKey('link_images_and_attributes', $settings);
        self::assertContains($settings['link_images_and_attributes'], [0, 1], true);
        self::assertArrayNotHasKey('image_attribute_data', $settings);
    }
}
