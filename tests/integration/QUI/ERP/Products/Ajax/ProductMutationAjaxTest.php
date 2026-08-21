<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductMutationAjaxTest extends AjaxTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testActivationEndpointsSupportSingleAndMultipleProducts(): void
    {
        $First = ProductTestHelper::createProduct('ajax-activation-first');
        $Second = ProductTestHelper::createProduct('ajax-activation-second');

        $this->invokeEndpoint(
            'products/activate.php',
            'package_quiqqer_products_ajax_products_activate',
            $First->getId()
        );
        self::assertTrue(Products::getNewProductInstance($First->getId())->isActive());

        $this->invokeEndpoint(
            'products/activate.php',
            'package_quiqqer_products_ajax_products_activate',
            json_encode([$First->getId(), $Second->getId()], JSON_THROW_ON_ERROR)
        );
        self::assertTrue(Products::getNewProductInstance($Second->getId())->isActive());

        $this->invokeEndpoint(
            'products/deactivate.php',
            'package_quiqqer_products_ajax_products_deactivate',
            json_encode([$First->getId(), $Second->getId()], JSON_THROW_ON_ERROR)
        );
        self::assertFalse(Products::getNewProductInstance($First->getId())->isActive());
        self::assertFalse(Products::getNewProductInstance($Second->getId())->isActive());

        $this->invokeEndpoint(
            'products/deactivate.php',
            'package_quiqqer_products_ajax_products_deactivate',
            $First->getId()
        );
        self::assertFalse(Products::getNewProductInstance($First->getId())->isActive());
    }

    public function testQuantityAndPublicFieldStatusEndpointsReturnEffectiveValues(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-quantity');

        self::assertSame(4.5, $this->invokeEndpoint(
            'products/setQuantity.php',
            'package_quiqqer_products_ajax_products_setQuantity',
            $Product->getId(),
            '4.5'
        ));
        self::assertSame(4.0, $this->invokeEndpoint(
            'products/setQuantity.php',
            'package_quiqqer_products_ajax_products_setQuantity',
            $Product->getId(),
            '4'
        ));

        self::assertFalse($this->invokeEndpoint(
            'products/setPublicStatusFromField.php',
            'package_quiqqer_products_ajax_products_setPublicStatusFromField',
            $Product->getId(),
            Fields::FIELD_PRICE,
            0
        ));
        Products::cleanProductInstanceMemCache($Product->getId());
        self::assertFalse(Products::getNewProductInstance($Product->getId())->getField(
            Fields::FIELD_PRICE
        )->isPublic());
    }

    public function testCreateCopyAndDeleteEndpointsPersistTheCompleteLifecycle(): void
    {
        $title = 'phpunit-products-dbal-ajax-created';
        $fields = [
            'field-' . Fields::FIELD_TITLE => ['de' => $title, 'en' => ''],
            'field-' . Fields::FIELD_PRICE => 12.75,
            'field-' . Fields::FIELD_PRODUCT_NO => 'phpunit-products-dbal-ajax-no'
        ];
        $created = $this->invokeEndpoint(
            'products/create.php',
            'package_quiqqer_products_ajax_products_create',
            ProductTestHelper::getCategory()->getId(),
            json_encode([ProductTestHelper::getCategory()->getId()], JSON_THROW_ON_ERROR),
            json_encode($fields, JSON_THROW_ON_ERROR),
            ''
        );
        $createdId = (int)$created['id'];
        self::assertTrue(Products::existsProduct($createdId));
        self::assertSame($title, Products::getNewProductInstance($createdId)->getTitle());

        $copyId = $this->invokeEndpoint(
            'products/copy.php',
            'package_quiqqer_products_ajax_products_copy',
            $createdId
        );
        self::assertNotSame($createdId, $copyId);
        self::assertTrue(Products::existsProduct($copyId));
        self::assertSame($title, Products::getNewProductInstance($copyId)->getTitle());

        $this->invokeEndpoint(
            'products/deleteChild.php',
            'package_quiqqer_products_ajax_products_deleteChild',
            $copyId
        );
        self::assertFalse(Products::existsProduct($copyId));

        $this->invokeEndpoint(
            'products/deleteChildren.php',
            'package_quiqqer_products_ajax_products_deleteChildren',
            json_encode([$createdId], JSON_THROW_ON_ERROR)
        );
        self::assertFalse(Products::existsProduct($createdId));
    }

    public function testUpdateEndpointPersistsFieldsCategoriesAndMainCategory(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-before-update', 13.5);
        $productId = $Product->getId();
        $categoryId = ProductTestHelper::getCategory()->getId();

        $result = $this->invokeEndpoint(
            'products/update.php',
            'package_quiqqer_products_ajax_products_update',
            $productId,
            json_encode([$categoryId, 999999], JSON_THROW_ON_ERROR),
            $categoryId,
            json_encode([
                'field-' . Fields::FIELD_TITLE => [
                    'de' => 'ajax-after-update',
                    'en' => 'ajax-after-update'
                ],
                'field-' . Fields::FIELD_PRICE => 27.75,
                'field-999999' => 'ignored unknown field'
            ], JSON_THROW_ON_ERROR)
        );

        self::assertNull($result);
        Products::cleanProductInstanceMemCache($productId);
        $Reloaded = Products::getNewProductInstance($productId);
        self::assertSame('ajax-after-update', $Reloaded->getTitle());
        self::assertSame(27.75, $Reloaded->getFieldValue(Fields::FIELD_PRICE));
        self::assertSame($categoryId, $Reloaded->getCategory()?->getId());
        self::assertContains(
            $categoryId,
            array_map(static fn ($Category): int => $Category->getId(), $Reloaded->getCategories())
        );
        self::assertNotContains(
            999999,
            array_map(static fn ($Category): int => $Category->getId(), $Reloaded->getCategories())
        );
    }

    public function testOwnFieldEndpointsPersistAdditionAndRemoval(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-own-fields');
        $productId = $Product->getId();
        $fieldIds = [Fields::FIELD_PRIORITY, Fields::FIELD_SHORT_DESC];

        foreach ($fieldIds as $fieldId) {
            self::assertFalse($Product->getField($fieldId)->isOwnField());
        }

        $result = $this->invokeEndpoint(
            'products/addField.php',
            'package_quiqqer_products_ajax_products_addField',
            $productId,
            json_encode($fieldIds, JSON_THROW_ON_ERROR)
        );
        self::assertNull($result);

        Products::cleanProductInstanceMemCache($productId);
        $Reloaded = Products::getNewProductInstance($productId);
        foreach ($fieldIds as $fieldId) {
            self::assertTrue($Reloaded->getField($fieldId)->isOwnField());
        }

        foreach ($fieldIds as $fieldId) {
            $result = $this->invokeEndpoint(
                'products/removeField.php',
                'package_quiqqer_products_ajax_products_removeField',
                $productId,
                $fieldId
            );
            self::assertNull($result);
            Products::cleanProductInstanceMemCache($productId);
        }

        $Reloaded = Products::getNewProductInstance($productId);
        foreach ($fieldIds as $fieldId) {
            self::assertTrue($Reloaded->hasField($fieldId));
            self::assertFalse($Reloaded->getField($fieldId)->isOwnField());
        }
    }

    public function testUrlCheckAndConsoleCommandEndpointsReturnActionableResults(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-url-check');
        $categoryId = ProductTestHelper::getCategory()->getId();

        self::assertSame([
            'exists' => false,
            'message' => ''
        ], $this->invokeEndpointAsAdmin(
            'products/checkUrl.php',
            'package_quiqqer_products_ajax_products_checkUrl',
            json_encode(['de' => ProductTestHelper::PREFIX . 'unused-url'], JSON_THROW_ON_ERROR),
            $categoryId
        ));

        $existing = $this->invokeEndpointAsAdmin(
            'products/checkUrl.php',
            'package_quiqqer_products_ajax_products_checkUrl',
            json_encode($Product->getFieldValue(Fields::FIELD_URL), JSON_THROW_ON_ERROR),
            $categoryId
        );
        self::assertTrue($existing['exists']);
        self::assertNotSame('', $existing['message']);

        self::assertSame(
            QUI::conf('globals', 'cms_dir')
            . 'console products:set-field-attributes-to-products --fieldId=' . Fields::FIELD_PRICE,
            $this->invokeEndpointAsAdmin(
                'products/getSetFieldAttributesToProductsCmd.php',
                'package_quiqqer_products_ajax_products_getSetFieldAttributesToProductsCmd',
                Fields::FIELD_PRICE
            )
        );
    }
}
