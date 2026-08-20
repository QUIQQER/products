<?php

namespace QUITests\ERP\Products\Integration\Field\Types;

use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Products;
use QUI\ERP\Products\Field\View;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductsTest extends ProductIntegrationTestCase
{
    public function testValidationAcceptsEmptyValuesAndProductIdArrays(): void
    {
        $Field = new Products(9400, ['name' => 'related-products']);

        foreach ([null, '', 0, [], [12, 34]] as $value) {
            $Field->validate($value);
            $Field->setValue($value);
            self::assertSame($Field->cleanup($value), $Field->getValue());
        }
    }

    public function testValidationRejectsNonEmptyScalarAndObjectValues(): void
    {
        $Field = new Products(9400, ['name' => 'related-products']);

        foreach (['12', 12, true, new \stdClass()] as $value) {
            try {
                $Field->validate($value);
                self::fail('A non-array related-products value must be rejected.');
            } catch (Exception $Exception) {
                self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
            }
        }
    }

    public function testCleanupKeepsOnlyExistingProductIdsInInputOrder(): void
    {
        $First = ProductTestHelper::createProduct('Related product one');
        $Second = ProductTestHelper::createProduct('Related product two');
        $Field = new Products(9400, ['name' => 'related-products']);

        self::assertSame(
            [$First->getId(), $Second->getId(), $First->getId()],
            $Field->cleanup([
                $First->getId(),
                0,
                PHP_INT_MAX,
                $Second->getId(),
                $First->getId()
            ])
        );
        self::assertSame([], $Field->cleanup(null));
        self::assertSame([], $Field->cleanup((string)$First->getId()));
    }

    public function testViewsAndMetadataDescribeRelatedProductBehavior(): void
    {
        $value = [101, 202];
        $Field = new Products(9400, [
            'name' => 'related-products',
            'public' => true,
            'value' => $value
        ]);

        $BackendView = $Field->getBackendView();
        $FrontendView = $Field->getFrontendView();

        self::assertInstanceOf(View::class, $BackendView);
        self::assertInstanceOf(View::class, $FrontendView);
        self::assertNotSame($BackendView, $FrontendView);
        self::assertSame($value, $BackendView->getValue());
        self::assertSame($value, $FrontendView->getValue());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Products',
            $Field->getJavaScriptControl()
        );
        self::assertFalse($Field->isSearchable());
        self::assertTrue($Field->showInDetails());
        self::assertTrue($Field->isEmpty());
        self::assertNull($Field->getSearchCacheValue());
    }
}
