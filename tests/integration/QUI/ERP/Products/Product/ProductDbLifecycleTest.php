<?php

namespace QUITests\ERP\Products\Integration\Product;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\Tables;

class ProductDbLifecycleTest extends ProductIntegrationTestCase
{
    public function testProductCanBeCreatedChangedActivatedDeactivatedAndDeleted(): void
    {
        $title = ProductTestHelper::PREFIX . 'lifecycle-' . bin2hex(random_bytes(6));
        $changedTitle = $title . '-changed';
        $Product = ProductTestHelper::createProduct($title, 42.5);
        $productId = $Product->getId();

        self::assertTrue(Products::existsProduct($productId));
        self::assertSame($title, $Product->getTitle());
        self::assertSame(42.5, $Product->getField(Fields::FIELD_PRICE)->getValue());
        self::assertSame(ProductTestHelper::getCategory()->getId(), $Product->getCategory()?->getId());
        self::assertSame(
            ProductTestHelper::getCategory()->getId(),
            (int)ProductTestHelper::getCategorySite()->getAttribute('quiqqer.products.settings.categoryId')
        );

        $localizedTitle = $Product->getField(Fields::FIELD_TITLE)->getValue();

        foreach ($localizedTitle as $language => $value) {
            $localizedTitle[$language] = $changedTitle;
        }

        $Product->getField(Fields::FIELD_TITLE)->setValue($localizedTitle);
        $Product->getField(Fields::FIELD_PRICE)->setValue(84.75);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->save($SystemUser);
        });

        Products::cleanProductInstanceMemCache($productId);
        $Reloaded = Products::getNewProductInstance($productId);

        self::assertSame($changedTitle, $Reloaded->getTitle());
        self::assertSame(84.75, $Reloaded->getField(Fields::FIELD_PRICE)->getValue());

        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Reloaded): void {
            $Reloaded->activate($SystemUser);
        });
        Products::cleanProductInstanceMemCache($productId);
        self::assertTrue(Products::getNewProductInstance($productId)->isActive());

        $activeProductQuery = [
            'where' => [
                'id' => $productId,
                'active' => 1,
                'type' => [
                    'type' => 'IN',
                    'value' => [$Reloaded::class]
                ]
            ]
        ];

        self::assertSame([$productId], array_map('intval', Products::getProductIds($activeProductQuery)));

        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($productId): void {
            Products::getNewProductInstance($productId)->deactivate($SystemUser);
        });
        Products::cleanProductInstanceMemCache($productId);
        self::assertFalse(Products::getNewProductInstance($productId)->isActive());
        self::assertSame([], Products::getProductIds($activeProductQuery));

        self::assertSame([$productId], array_map('intval', Products::getProductIds([
            'where' => ['id' => $productId]
        ])));
        self::assertSame(1, Products::countProducts(['where' => ['id' => $productId]]));
        self::assertGreaterThan(0, $this->countCacheRows($productId));

        ProductTestHelper::runAsSystemUser(static function () use ($productId): void {
            Products::getNewProductInstance($productId)->delete();
        });
        Products::cleanProductInstanceMemCache($productId);

        self::assertFalse(Products::existsProduct($productId));
        self::assertSame(0, $this->countCacheRows($productId));
    }

    public function testProductLookupByProductNumberUsesPersistedCache(): void
    {
        $Product = ProductTestHelper::createProduct();
        $productId = $Product->getId();
        $productNo = (string)$Product->getField(Fields::FIELD_PRODUCT_NO)->getValue();

        Products::cleanProductInstanceMemCache($productId);
        $Found = Products::getProductByProductNo($productNo);

        self::assertSame($productId, $Found->getId());
        self::assertSame($productNo, $Found->getField(Fields::FIELD_PRODUCT_NO)->getValue());
    }

    private function countCacheRows(int $productId): int
    {
        $QueryBuilder = QUI::getQueryBuilder();

        return (int)$QueryBuilder
            ->select('COUNT(id)')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductCacheTableName()))
            ->where($QueryBuilder->expr()->eq('id', ':id'))
            ->setParameter('id', $productId)
            ->executeQuery()
            ->fetchOne();
    }
}
