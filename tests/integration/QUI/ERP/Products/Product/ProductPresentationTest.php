<?php

namespace QUITests\ERP\Products\Integration\Product;

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\JsonLd;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Product\ProductListBackendView;
use QUI\ERP\Products\Product\ProductListFrontendView;
use QUI\ERP\Products\Product\UniqueProductFrontendView;

class ProductPresentationTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testBackendAndFrontendViewsExposeTheSamePersistedProduct(): void
    {
        $Product = ProductTestHelper::createProduct('presentation-product', 28.5);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });

        $Backend = $Product->getViewBackend();
        self::assertSame($Product->getId(), $Backend->getId());
        self::assertSame($Product, $Backend->getProduct());
        self::assertSame('presentation-product', $Backend->getTitle());
        self::assertSame('', $Backend->getDescription());
        self::assertSame('', $Backend->getContent());
        self::assertSame(28.5, $Backend->getPrice()->value());
        self::assertSame(28.5, $Backend->getMinimumPrice()->value());
        self::assertSame(28.5, $Backend->getMaximumPrice()->value());
        self::assertSame(28.5, $Backend->getFieldValue(Fields::FIELD_PRICE));
        self::assertSame(Fields::FIELD_PRICE, $Backend->getField(Fields::FIELD_PRICE)?->getId());
        self::assertNotEmpty($Backend->getFields());
        self::assertNotEmpty($Backend->getFieldsByType(Fields::TYPE_PRICE));
        self::assertSame(ProductTestHelper::getCategory()->getId(), $Backend->getCategory()?->getId());
        self::assertFalse($Backend->hasOfferPrice());

        $backendData = $Backend->getAttributes();
        self::assertSame($Product->getId(), $backendData['id']);
        self::assertSame('presentation-product', $backendData['title']);

        $Frontend = $Product->getViewFrontend();
        $frontendData = $Frontend->getAttributes();
        self::assertSame($Product->getId(), $frontendData['id']);
        self::assertSame('presentation-product', $frontendData['title']);
        self::assertSame(28.5, $frontendData['price_netto']);
        self::assertSame('EUR', $frontendData['price_currency']);
        self::assertContains((string)ProductTestHelper::getCategory()->getId(), explode(',', $frontendData['categories']));
    }

    public function testJsonLdContainsProductIdentityAndOfferData(): void
    {
        $Product = ProductTestHelper::createProduct('structured-product', 41.25);
        $productNo = (string)$Product->getFieldValue(Fields::FIELD_PRODUCT_NO);

        $data = JsonLd::parse($Product);
        self::assertSame('https://schema.org/', $data['@context']);
        self::assertSame('Product', $data['@type']);
        self::assertSame('structured-product', $data['name']);
        self::assertSame($productNo, $data['sku']);
        self::assertSame('Offer', $data['offers']['@type']);
        self::assertSame('EUR', $data['offers']['priceCurrency']);
        self::assertSame('InStock', $data['offers']['availability']);

        $html = JsonLd::getJsonLd($Product);
        self::assertStringStartsWith('<script type="application/ld+json">', $html);
        self::assertStringContainsString('"name":"structured-product"', $html);
        self::assertStringEndsWith('</script>', $html);
    }

    public function testUniqueAndListViewsRenderCalculatedCustomerData(): void
    {
        $Product = ProductTestHelper::createProduct('list-presentation', 12.5);
        $Unique = $Product->createUniqueProduct();
        $Unique->setQuantity(2);
        $Unique->calc();

        $FrontendUnique = $Unique->getView();
        self::assertInstanceOf(UniqueProductFrontendView::class, $FrontendUnique);
        self::assertSame(25.0, $FrontendUnique->getPrice()->value());
        self::assertSame(12.5, $FrontendUnique->getUnitPrice()->value());
        self::assertFalse($FrontendUnique->hasOfferPrice());
        self::assertSame(25.0, $FrontendUnique->getOriginalPrice()->value());
        self::assertSame($Product->getId(), $FrontendUnique->getAttributes()['id']);

        $List = new ProductList([], $Unique->getUser());
        $List->addProduct($Unique);
        $List->calc();
        self::assertSame(1, $List->count());
        self::assertSame(2.0, $List->getQuantity());

        $FrontendList = $List->getFrontendView();
        self::assertInstanceOf(ProductListFrontendView::class, $FrontendList);
        self::assertSame(1, $FrontendList->count());
        self::assertCount(1, $FrontendList->getProducts());
        self::assertSame('list-presentation', $FrontendList->getProducts()[0]['title']);
        self::assertNotSame('', $FrontendList->getSum());
        self::assertJson($FrontendList->toJSON());
        self::assertStringContainsString('list-presentation', $FrontendList->toHTML(false));

        $BackendList = $List->getBackendView();
        self::assertInstanceOf(ProductListBackendView::class, $BackendList);
        self::assertCount(1, $BackendList->getProducts());
        self::assertNotSame('', $BackendList->getSum());
        self::assertJson($BackendList->toJSON());
        self::assertStringContainsString('list-presentation', $BackendList->toHTML(false));

        $FrontendList->hidePrices();
        self::assertTrue($FrontendList->isPriceHidden());
        $FrontendList->showPrices();
        self::assertFalse($FrontendList->isPriceHidden());
        $BackendList->hidePrices();
        self::assertTrue($BackendList->isPriceHidden());
        $BackendList->showPrices();
        self::assertFalse($BackendList->isPriceHidden());
    }
}
