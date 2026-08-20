<?php

namespace QUITests\ERP\Products\Integration\Console;

use QUI\ERP\Products\Console\UpdatePrices;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class UpdatePricesTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testExecuteUpdatesOnlyActiveProductsInSelectedCategory(): void
    {
        $Active = ProductTestHelper::createProduct('console-active-price', 12.0);
        $Inactive = ProductTestHelper::createProduct('console-inactive-price', 8.0);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Active): void {
            $Active->activate($SystemUser);
        });
        $Settings = new ReflectionProperty(Fields::class, 'priceFactorSettings');
        $originalSettings = $Settings->getValue();
        $Settings->setValue(null, [
            Fields::FIELD_PRICE_OFFER => [
                'sourceFieldId' => Fields::FIELD_PRICE,
                'multiplier' => 2,
                'updateOnSave' => false
            ]
        ]);

        try {
            $Tool = new UpdatePrices();
            self::assertSame('products:update-prices', $Tool->getName());
            self::assertArrayHasKey('activeOnly', $Tool->getArgumentDefinitions());
            self::assertArrayHasKey('categoryId', $Tool->getArgumentDefinitions());

            $Tool->setArgument('activeOnly', '1');
            $Tool->setArgument('categoryId', (string)ProductTestHelper::getCategory()->getId());
            $Tool->execute();

            Products::cleanProductInstanceMemCache();
            self::assertSame(
                24.0,
                Products::getNewProductInstance($Active->getId())->getFieldValue(Fields::FIELD_PRICE_OFFER)
            );
            self::assertNull(
                Products::getNewProductInstance($Inactive->getId())->getFieldValue(Fields::FIELD_PRICE_OFFER)
            );

            self::assertSame(
                2,
                $Tool->updateProductPrices(false, ProductTestHelper::getCategory()->getId())
            );
            Products::cleanProductInstanceMemCache();
            self::assertSame(
                16.0,
                Products::getNewProductInstance($Inactive->getId())->getFieldValue(Fields::FIELD_PRICE_OFFER)
            );
            self::assertSame(0, $Tool->updateProductPrices(false, 999999));
        } finally {
            $Settings->setValue(null, $originalSettings);
        }
    }

    public function testProductsWithoutRelevantPriceFieldsAreNotUpdated(): void
    {
        $Product = ProductTestHelper::createProduct('console-irrelevant-price', 10.0);
        $Settings = new ReflectionProperty(Fields::class, 'priceFactorSettings');
        $originalSettings = $Settings->getValue();
        $Settings->setValue(null, [
            999999 => [
                'sourceFieldId' => 999998,
                'multiplier' => 2,
                'updateOnSave' => true
            ]
        ]);

        try {
            self::assertSame(0, (new UpdatePrices())->updateProductPrices());
            Products::cleanProductInstanceMemCache($Product->getId());
            self::assertNull(
                Products::getNewProductInstance($Product->getId())->getFieldValue(Fields::FIELD_PRICE_OFFER)
            );
        } finally {
            $Settings->setValue(null, $originalSettings);
        }
    }

    public function testCategorySpecificPriceFactorsTriggerPersistentUpdate(): void
    {
        $Product = ProductTestHelper::createProduct('console-category-price', 5.0);
        $Category = ProductTestHelper::getCategory();
        $originalCategorySettings = $Category->getCustomDataEntry('priceFieldFactors');
        $Settings = new ReflectionProperty(Fields::class, 'priceFactorSettings');
        $originalGlobalSettings = $Settings->getValue();
        $Settings->setValue(null, []);
        $categorySettings = [
            Fields::FIELD_PRICE_OFFER => [
                'sourceFieldId' => Fields::FIELD_PRICE,
                'multiplier' => 3,
                'updateOnSave' => false
            ]
        ];
        $Category->setCustomDataEntry('priceFieldFactors', $categorySettings);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Category): void {
            $Category->save($SystemUser);
        });
        Products::cleanProductInstanceMemCache($Product->getId());

        try {
            $Reloaded = Products::getNewProductInstance($Product->getId());
            self::assertSame(
                $categorySettings,
                $Reloaded->getCategory()?->getCustomDataEntry('priceFieldFactors')
            );
            Products::cleanProductInstanceMemCache($Product->getId());
            self::assertSame(1, (new UpdatePrices())->updateProductPrices());
            Products::cleanProductInstanceMemCache($Product->getId());
            self::assertSame(
                15.0,
                Products::getNewProductInstance($Product->getId())->getFieldValue(Fields::FIELD_PRICE_OFFER)
            );
        } finally {
            $Settings->setValue(null, $originalGlobalSettings);
            $Category->setCustomDataEntry('priceFieldFactors', $originalCategorySettings);
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Category): void {
                $Category->save($SystemUser);
            });
        }
    }
}
