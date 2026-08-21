<?php

namespace QUITests\ERP\Products\Integration\DemoData;

use QUI\ERP\DemoData\DTO\DemoDataCreationContext;
use QUI\ERP\DemoData\DTO\DemoDataReference;
use QUI\ERP\DemoData\DTO\DemoDataReferenceCollection;
use QUI\ERP\DemoData\Exception\DemoDataException;
use QUI\ERP\Products\DemoData\ProductDemoDataCreator;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductDemoDataCreatorTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testCreatorPersistsTenReferencedProductsAndDeletesThemAgain(): void
    {
        $Creator = new ProductDemoDataCreator();
        self::assertSame([], $Creator->getDependencies());
        $beforeIds = array_map('intval', Products::getProductIds());
        $createdIds = [];

        try {
            $Context = new DemoDataCreationContext(new DemoDataReferenceCollection());
            $created = $Creator->createDemoData($Context)->all();

            self::assertCount(10, $created);
            self::assertSame(
                array_map(static fn (int $number): string => 'product_' . $number, range(1, 10)),
                array_column($created, 'referenceKey')
            );
            $createdIds = array_map(
                static fn ($entry): int => (int)$entry->entityUuid,
                $created
            );
            self::assertCount(10, array_unique($createdIds));

            $productNumbers = [];
            foreach ($createdIds as $index => $productId) {
                $Product = Products::getNewProductInstance($productId);
                self::assertSame('Demo product ' . ($index + 1), $Product->getTitle());
                self::assertEqualsWithDelta(
                    19.99 + ($index * 20),
                    $Product->getPrice()->value(),
                    0.000001
                );
                $productNumbers[] = $Product->getFieldValue(Fields::FIELD_PRODUCT_NO);
            }
            self::assertCount(10, array_unique($productNumbers));
            foreach ($productNumbers as $productNumber) {
                self::assertMatchesRegularExpression('/^DEMO-[A-F0-9]{12}$/', (string)$productNumber);
            }

            $references = array_map(
                static fn ($entry): DemoDataReference => new DemoDataReference(
                    'quiqqer.products',
                    $entry->entityType,
                    $entry->entityUuid,
                    $entry->referenceKey
                ),
                $created
            );
            ProductTestHelper::runAsSystemUser(static function () use ($Creator, $references): void {
                $Creator->deleteDemoData(new DemoDataReferenceCollection([
                    'quiqqer.products' => $references
                ]));
            });

            foreach ($createdIds as $productId) {
                self::assertFalse(Products::existsProduct($productId));
            }
            $createdIds = [];
        } finally {
            $remainingIds = array_diff(array_map('intval', Products::getProductIds()), $beforeIds);
            $remainingIds = array_unique(array_merge($createdIds, $remainingIds));

            foreach ($remainingIds as $productId) {
                try {
                    ProductTestHelper::runAsSystemUser(
                        static fn () => Products::deleteProduct((int)$productId)
                    );
                } catch (\Throwable) {
                }
            }
        }
    }

    public function testDeleteRejectsInvalidProductReference(): void
    {
        $references = new DemoDataReferenceCollection([
            'quiqqer.products' => [
                new DemoDataReference('quiqqer.products', 'category', '12', 'wrong-type')
            ]
        ]);

        $this->expectException(DemoDataException::class);
        $this->expectExceptionMessage('invalid entity type or identifier');

        (new ProductDemoDataCreator())->deleteDemoData($references);
    }
}
