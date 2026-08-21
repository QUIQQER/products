<?php

namespace QUITests\ERP\Products\Integration\Product;

use QUI\ERP\Accounting\Article;
use QUI\ERP\Currency\Currency;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Exception as ProductException;
use QUI\ERP\Products\Product\UniqueProduct;

class UniqueProductBehaviorTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testUniqueProductIsAStableCustomerSpecificSnapshot(): void
    {
        $Product = ProductTestHelper::createProduct('unique-snapshot', 31.5);
        $Unique = $Product->createUniqueProduct();

        self::assertInstanceOf(UniqueProduct::class, $Unique);
        self::assertSame($Product->getId(), $Unique->getId());
        self::assertNotSame('', $Unique->getUuid());
        self::assertSame('unique-snapshot', $Unique->getTitle());
        self::assertSame('', $Unique->getDescription());
        self::assertSame('', $Unique->getContent());
        self::assertSame(31.5, $Unique->getFieldValue(Fields::FIELD_PRICE));
        self::assertNull($Unique->getField(999999));
        self::assertNotEmpty($Unique->getFields());
        self::assertNotEmpty($Unique->getFieldsByType(Fields::TYPE_PRICE));
        self::assertArrayHasKey(Fields::FIELD_PRICE, $Unique->getPublicFields());
        self::assertSame(ProductTestHelper::getCategory()->getId(), $Unique->getCategory()?->getId());
        $categoryIds = array_map(static fn ($Category): int => $Category->getId(), $Unique->getCategories());
        self::assertContains(ProductTestHelper::getCategory()->getId(), $categoryIds);
        self::assertContains(0, $categoryIds);

        $firstIdentifier = $Unique->getCacheIdentifier();
        $Unique->setProductSetParentUuid('set-parent');
        self::assertSame('set-parent', $Unique->getProductSetParentUuid());
        self::assertNotSame($firstIdentifier, $Unique->getCacheIdentifier());

        $Unique->setQuantity(-2);
        self::assertSame(0.0, $Unique->getQuantity());
        $Unique->setQuantity(3);
        self::assertSame(3.0, $Unique->getQuantity());
        self::assertSame(94.5, $Unique->getPrice()->value());
        self::assertSame(31.5, $Unique->getUnitPrice()->value());
        self::assertSame(94.5, $Unique->getMinimumPrice()->value());
        self::assertSame(94.5, $Unique->getMaximumPrice()->value());
        self::assertFalse($Unique->hasOfferPrice());
        self::assertTrue($Unique->getMaximumQuantity());

        $data = $Unique->toArray();
        self::assertSame($Unique->getUuid(), $data['uuid']);
        self::assertSame('set-parent', $data['productSetParentUuid']);
        self::assertSame(3.0, $data['quantity']);
        self::assertArrayHasKey('price_display', $data);
        self::assertArrayHasKey('calculated_sum', $data);
    }

    public function testUniqueProductCreatesCompleteErpArticle(): void
    {
        $Product = ProductTestHelper::createProduct('article-snapshot', 18.75);
        $attributes = $Product->createUniqueProduct()->getAttributes();
        $attributes['uuid'] = 'article-uuid';
        $attributes['productSetParentUuid'] = 'set-parent-uuid';
        $attributes['minimumPrice'] = 15.5;
        $attributes['maximumPrice'] = 24.5;
        $attributes['maximumQuantity'] = 2;
        $attributes['quantity'] = 5;
        $attributes['displayPrice'] = false;
        $attributes['customData'] = [
            'configuration' => 'engraved'
        ];
        $attributes['fields'][] = new UniqueField(990001, [
            'title' => 'Engraving',
            'type' => Fields::TYPE_INPUT,
            'custom' => true,
            'value' => 'For Ada',
            'options' => [
                'internal' => 'must-not-be-persisted'
            ]
        ]);

        $Unique = new UniqueProduct($Product->getId(), $attributes);

        self::assertSame('article-uuid', $Unique->getUuid());
        self::assertSame('set-parent-uuid', $Unique->getProductSetParentUuid());
        self::assertSame(2.0, $Unique->getQuantity());
        self::assertSame(15.5, $Unique->getMinimumPrice()->value());
        self::assertSame(24.5, $Unique->getMaximumPrice()->value());
        self::assertSame(
            ProductTestHelper::getCategory()->getId(),
            $Unique->getCategory()?->getId()
        );

        $Unique->calc();
        $Article = $Unique->toArticle();

        self::assertInstanceOf(Article::class, $Article);
        self::assertSame($Product->getId(), $Article->getId());
        self::assertSame('article-uuid', $Article->getUuid());
        self::assertSame('set-parent-uuid', $Article->getProductSetParentUuid());
        self::assertSame('article-snapshot', $Article->getTitle());
        self::assertNotSame('', $Article->getArticleNo());
        self::assertSame(2.0, $Article->getQuantity());
        self::assertSame(37.5, $Article->getSum()->value());
        self::assertFalse($Article->displayPrice());
        self::assertSame(
            ['configuration' => 'engraved'],
            $Article->getCustomData()
        );

        $customFields = $Article->getCustomFields();
        self::assertArrayHasKey(990001, $customFields);
        self::assertSame('For Ada', $customFields[990001]['value']);
        self::assertArrayNotHasKey('options', $customFields[990001]);
    }

    public function testUniqueProductRejectsSnapshotWithoutUser(): void
    {
        $this->expectException(ProductException::class);

        new UniqueProduct(990002);
    }

    public function testUniqueProductConvertsPriceAndCurrencyTogether(): void
    {
        $Product = ProductTestHelper::createProduct('currency-snapshot', 18.75);
        $attributes = $Product->createUniqueProduct()->getAttributes();
        $attributes['minimumPrice'] = 15;
        $attributes['maximumPrice'] = 25;
        $Unique = new UniqueProduct($Product->getId(), $attributes);
        $TargetCurrency = new Currency([
            'currency' => 'XTS',
            'rate' => 2,
            'autoupdate' => 0,
            'precision' => 2
        ]);

        self::assertNotSame('XTS', $Unique->getCurrency()->getCode());

        $Unique->convert($TargetCurrency);

        self::assertSame(37.5, $Unique->getFieldValue(Fields::FIELD_PRICE));
        self::assertSame('XTS', $Unique->getCurrency()->getCode());
        self::assertSame(30.0, $Unique->getMinimumPrice()->value());
        self::assertSame('XTS', $Unique->getMinimumPrice()->getCurrency()->getCode());
        self::assertSame(50.0, $Unique->getMaximumPrice()->value());
        self::assertSame('XTS', $Unique->getMaximumPrice()->getCurrency()->getCode());
        self::assertSame(37.5, $Unique->getUnitPrice()->value());
        self::assertSame('XTS', $Unique->getUnitPrice()->getCurrency()->getCode());
        self::assertSame('XTS', $Unique->getPrice()->getCurrency()->getCode());
    }
}
