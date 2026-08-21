<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductReadAjaxTest extends AjaxTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testBackendProductEndpointsReturnPersistedProductData(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-read-product', 17.25);
        $productId = $Product->getId();
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });

        $data = $this->invokeEndpoint(
            'products/get.php',
            'package_quiqqer_products_ajax_products_get',
            $productId
        );
        self::assertSame($productId, $data['id']);
        self::assertSame('ajax-read-product', $data['title']);
        self::assertSame(17.25, $data['price_netto']);
        self::assertSame('EUR', $data['price_currency']);
        self::assertNotEmpty($data['fields']);

        self::assertGreaterThanOrEqual(1, $this->invokeEndpoint(
            'products/getCount.php',
            'package_quiqqer_products_ajax_products_getCount'
        ));

        $selectData = $this->invokeEndpoint(
            'products/getDataForSelectItem.php',
            'package_quiqqer_products_ajax_products_getDataForSelectItem',
            $productId
        );
        self::assertSame($productId, $selectData['id']);
        self::assertSame('ajax-read-product', $selectData['title']);

        $categories = $this->invokeEndpoint(
            'products/getFieldCategories.php',
            'package_quiqqer_products_ajax_products_getFieldCategories',
            $productId
        );
        self::assertSame([], $categories);

        $categoryFields = $this->invokeEndpoint(
            'products/getFieldCategory.php',
            'package_quiqqer_products_ajax_products_getFieldCategory',
            ProductTestHelper::getCategory()->getId(),
            $productId
        );
        self::assertSame([], $categoryFields);
    }

    public function testFrontendProductLookupReturnsCustomerFacingData(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-frontend-product', 19.5);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $productNo = (string)$Product->getFieldValue(Fields::FIELD_PRODUCT_NO);

        $byNumber = $this->invokeEndpoint(
            'products/frontend/getProductByProductNo.php',
            'package_quiqqer_products_ajax_products_frontend_getProductByProductNo',
            $productNo
        );
        self::assertSame($Product->getId(), $byNumber['id']);
        self::assertSame('ajax-frontend-product', $byNumber['title']);
        $fieldValues = array_column($byNumber['fields'], 'value', 'id');
        self::assertSame($productNo, $fieldValues[Fields::FIELD_PRODUCT_NO]);

        $tracking = $this->invokeEndpoint(
            'products/frontend/getTrackingDataForProduct.php',
            'package_quiqqer_products_ajax_products_frontend_getTrackingDataForProduct',
            $Product->getId()
        );
        self::assertSame($Product->getId(), $tracking['id']);
        self::assertSame('ajax-frontend-product', $tracking['title']);
        self::assertSame($productNo, $tracking['productNo']);
        self::assertSame(19.5, $tracking['price']);
        self::assertNotEmpty($tracking['categories']);

        $dataLayerEvent = $this->invokeEndpoint(
            'products/frontend/dataLayer/getProductData.php',
            'package_quiqqer_products_ajax_products_frontend_dataLayer_getProductData',
            $Product->getId()
        );
        self::assertSame('EUR', $dataLayerEvent['currency']);
        self::assertSame(19.5, $dataLayerEvent['value']);
        self::assertSame($productNo, $dataLayerEvent['items'][0]['item_id']);
        self::assertSame('ajax-frontend-product', $dataLayerEvent['items'][0]['item_name']);
        self::assertSame(1, $dataLayerEvent['items'][0]['quantity']);

        $dataLayerList = $this->invokeEndpoint(
            'products/frontend/dataLayer/getProductListData.php',
            'package_quiqqer_products_ajax_products_frontend_dataLayer_getProductListData',
            json_encode([$Product->getId()], JSON_THROW_ON_ERROR),
            4
        );
        self::assertSame($productNo, $dataLayerList['items'][0]['item_id']);
        self::assertSame(4, $dataLayerList['items'][0]['index']);
    }

    public function testProductMetadataEndpointsExposeInstalledTypesAndFolder(): void
    {
        $types = $this->invokeEndpoint(
            'products/getProductTypes.php',
            'package_quiqqer_products_ajax_products_getProductTypes'
        );
        self::assertNotEmpty($types);

        $folder = $this->invokeEndpoint(
            'products/getParentFolder.php',
            'package_quiqqer_products_ajax_products_getParentFolder'
        );
        self::assertSame(ProductTestHelper::getProject()->getName(), $folder['project']);
        self::assertGreaterThan(0, (int)$folder['id']);
    }

    public function testProductListReturnsPersistedProductsSortedByTitle(): void
    {
        $Zulu = ProductTestHelper::createProduct('ajax-list-zulu', 24.0);
        $Alpha = ProductTestHelper::createProduct('ajax-list-alpha', 12.0);

        $result = $this->invokeEndpointAsAdmin(
            'products/list.php',
            'package_quiqqer_products_ajax_products_list',
            json_encode([
                'page' => 1,
                'perPage' => 100
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame(2, $result['total']);
        self::assertSame(
            [$Alpha->getId(), $Zulu->getId()],
            array_map('intval', array_column($result['data'], 'id'))
        );
        self::assertSame(
            ['ajax-list-alpha', 'ajax-list-zulu'],
            array_column($result['data'], 'title')
        );
        self::assertSame([12.0, 24.0], array_column($result['data'], 'price'));
    }

    public function testGetChildrenReturnsAllRequestedProductAttributes(): void
    {
        $First = ProductTestHelper::createProduct('ajax-child-first', 7.5);
        $Second = ProductTestHelper::createProduct('ajax-child-second', 9.25);

        $result = $this->invokeEndpointAsAdmin(
            'products/getChildren.php',
            'package_quiqqer_products_ajax_products_getChildren',
            json_encode([$Second->getId(), $First->getId()], JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            [$Second->getId(), $First->getId()],
            array_map('intval', array_column($result, 'id'))
        );
        self::assertSame(9.25, (float)$result[0]['price_netto']);
        self::assertSame(7.5, (float)$result[1]['price_netto']);
    }

    public function testGetChildrenRejectsUnknownProduct(): void
    {
        $this->expectException(\QUI\Exception::class);

        $this->invokeEndpointAsAdmin(
            'products/getChildren.php',
            'package_quiqqer_products_ajax_products_getChildren',
            json_encode([999999], JSON_THROW_ON_ERROR)
        );
    }

    public function testFrontendProductEndpointRendersRequestedProductAndRejectsUnknownId(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-rendered-product', 33.25);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Project = ProductTestHelper::getProject();
        $projectData = json_encode([
            'name' => $Project->getName(),
            'lang' => $Project->getLang()
        ], JSON_THROW_ON_ERROR);

        $result = $this->invokeEndpoint(
            'products/frontend/getProduct.php',
            'package_quiqqer_products_ajax_products_frontend_getProduct',
            $Product->getId(),
            $projectData,
            ProductTestHelper::getCategorySite()->getId()
        );

        self::assertIsArray($result);
        self::assertStringContainsString('ajax-rendered-product', $result['html']);
        self::assertStringContainsString('33,25', $result['html']);
        self::assertNotSame('', $result['title']);
        self::assertSame([], $result['fieldHashes']);
        self::assertIsArray($result['availableHashes']);
        self::assertIsString($result['css']);

        self::assertSame('', $this->invokeEndpoint(
            'products/frontend/getProduct.php',
            'package_quiqqer_products_ajax_products_frontend_getProduct',
            999999,
            $projectData,
            ProductTestHelper::getCategorySite()->getId()
        ));
    }

    public function testFrontendControlEndpointsDistinguishProductsVariantsAndMissingIds(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-control-product');
        $Variant = ProductTestHelper::createProduct(
            'ajax-control-variant',
            42.5,
            VariantParent::class
        );

        self::assertSame(
            'package/quiqqer/products/bin/controls/frontend/products/Product',
            $this->invokeEndpoint(
                'products/frontend/getProductControlClass.php',
                'package_quiqqer_products_ajax_products_frontend_getProductControlClass',
                $Product->getId()
            )
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/frontend/products/ProductVariant',
            $this->invokeEndpoint(
                'products/frontend/getProductControlClass.php',
                'package_quiqqer_products_ajax_products_frontend_getProductControlClass',
                $Variant->getId()
            )
        );

        self::assertSame(
            ['link_images_and_attributes' => 0],
            $this->invokeEndpoint(
                'products/frontend/getVariantControlSettings.php',
                'package_quiqqer_products_ajax_products_frontend_getVariantControlSettings',
                $Variant->getId()
            )
        );
        self::assertSame([], $this->invokeEndpoint(
            'products/frontend/getVariantControlSettings.php',
            'package_quiqqer_products_ajax_products_frontend_getVariantControlSettings',
            999999
        ));
    }
}
