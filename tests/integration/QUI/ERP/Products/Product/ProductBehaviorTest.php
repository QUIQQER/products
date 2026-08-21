<?php

namespace QUITests\ERP\Products\Integration\Product;

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

class ProductBehaviorTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testProductExposesPersistedBusinessDataAndCalculatedPrices(): void
    {
        $Product = ProductTestHelper::createProduct('public-product-data', 23.75);

        self::assertGreaterThan(0, $Product->getId());
        self::assertSame('public-product-data', $Product->getTitle());
        self::assertSame('', $Product->getDescription());
        self::assertSame('', $Product->getContent());
        self::assertSame(23.75, $Product->getFieldValue(Fields::FIELD_PRICE));
        self::assertTrue($Product->hasField(Fields::FIELD_PRICE));
        self::assertFalse($Product->hasField(999999));
        self::assertSame(
            Fields::FIELD_PRICE,
            $Product->getFieldsByType(Fields::TYPE_PRICE)[0]->getId()
        );
        self::assertSame(ProductTestHelper::getCategory()->getId(), $Product->getCategory()?->getId());
        $categoryIds = array_map(static fn ($Category): int => $Category->getId(), $Product->getCategories());
        self::assertContains(ProductTestHelper::getCategory()->getId(), $categoryIds);
        self::assertContains(0, $categoryIds);

        self::assertSame(23.75, $Product->getPrice()->value());
        self::assertSame(23.75, $Product->getCurrentPrice()->value());
        self::assertSame(23.75, $Product->getMinimumPrice()->value());
        self::assertSame(23.75, $Product->getMaximumPrice()->value());
        self::assertFalse($Product->hasOfferPrice());
        self::assertTrue($Product->getMaximumQuantity());

        $attributes = $Product->getAttributes();
        self::assertSame($Product->getId(), $attributes['id']);
        self::assertSame('public-product-data', $attributes['title']);
        self::assertArrayHasKey('fields', $attributes);
        self::assertArrayHasKey('categories', $attributes);
        $validatedFields = $Product->validateFields();
        $validatedIds = array_column($validatedFields, 'id');
        self::assertContains(Fields::FIELD_PRICE, $validatedIds);
        self::assertContains(Fields::FIELD_PRODUCT_NO, $validatedIds);
        self::assertContains(Fields::FIELD_TITLE, $validatedIds);
        self::assertNotSame('', $Product->getUrl(ProductTestHelper::getProject()));
        self::assertNotSame('', $Product->getUrlRewrittenWithHost(ProductTestHelper::getProject()));
        self::assertNotSame('', $Product->getUrlName());
    }

    public function testCategoryChangesAreSavedAndReflectedByHandlerQueries(): void
    {
        $Product = ProductTestHelper::createProduct('category-change');
        $productId = $Product->getId();
        $categoryId = ProductTestHelper::getCategory()->getId();

        $Product->removeCategory($categoryId);
        self::assertSame([0], array_map(
            static fn ($Category): int => $Category->getId(),
            $Product->getCategories()
        ));
        $Product->clearCategories();

        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->save($SystemUser);
        });

        Products::cleanProductInstanceMemCache($productId);
        $Reloaded = Products::getNewProductInstance($productId);
        self::assertSame([0], array_map(
            static fn ($Category): int => $Category->getId(),
            $Reloaded->getCategories()
        ));
        self::assertSame(0, $Reloaded->getCategory()?->getId());
    }

    public function testProductMediaFolderIsCreatedOnceAndCanBeListed(): void
    {
        $Product = ProductTestHelper::createProduct('media-folder');

        self::assertFalse($Product->hasImage());
        self::assertSame([], $Product->getImages());
        self::assertSame([], $Product->getFiles());

        $Folder = ProductTestHelper::runAsSystemUser(
            static fn () => $Product->createMediaFolder()
        );

        self::assertSame($Folder->getId(), $Product->getMediaFolder()->getId());
        self::assertSame($Folder->getId(), $Product->createMediaFolder()->getId());
    }
}
