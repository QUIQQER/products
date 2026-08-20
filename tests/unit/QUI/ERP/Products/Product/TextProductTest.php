<?php

namespace QUITests\ERP\Products\Unit\Product;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\Types\Price;
use QUI\ERP\Products\Field\Types\Textarea;
use QUI\ERP\Products\Field\Types\Vat;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Exception;
use QUI\ERP\Products\Product\TextProduct;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\Rewrite;
use QUITests\ERP\Products\Fixtures\TestUser;

class TextProductTest extends TestCase
{
    private ?Rewrite $originalRewrite;

    protected function setUp(): void
    {
        $this->originalRewrite = QUI::$Rewrite;
    }

    protected function tearDown(): void
    {
        QUI::$Rewrite = $this->originalRewrite;
    }

    public function testIdentityTextAndStructuralDefaults(): void
    {
        $Product = $this->createTextProduct();

        self::assertSame(-1, $Product->getId());
        self::assertSame('Manual service', $Product->getTitle());
        self::assertSame('Individually described service', $Product->getDescription());
        self::assertSame('', $Product->getContent());
        self::assertSame(1, $Product->getMaximumQuantity());
        self::assertNull($Product->getCategory());
        self::assertSame([], $Product->getCategories());
        self::assertSame([], $Product->getImages());
        self::assertSame([], $Product->getFieldsByType(Fields::TYPE_INPUT));
        self::assertFalse($Product->hasOfferPrice());
        self::assertSame(0, $Product->getAttribute('vat'));
        self::assertSame([], $Product->getAttribute('calculated')['vatArray']);
    }

    public function testGeneratedFieldsRepresentTextProductData(): void
    {
        $Product = $this->createTextProduct();

        self::assertInstanceOf(Price::class, $Product->getField(Fields::FIELD_PRICE));
        self::assertInstanceOf(Vat::class, $Product->getField(Fields::FIELD_VAT));
        self::assertInstanceOf(Textarea::class, $Product->getField(Fields::FIELD_CONTENT));

        $ProductNo = $Product->getField(Fields::FIELD_PRODUCT_NO);
        self::assertInstanceOf(Input::class, $ProductNo);
        self::assertSame('MANUAL-01', $ProductNo->getValue());

        $Title = $Product->getField(Fields::FIELD_TITLE);
        self::assertInstanceOf(Input::class, $Title);
        self::assertSame('Manual service', $Title->getValue());

        $fields = $Product->getFields();
        self::assertCount(5, $fields);
        self::assertContains(Fields::FIELD_PRICE, array_map(static fn ($Field): int => $Field->getId(), $fields));
        self::assertContains(Fields::FIELD_VAT, array_map(static fn ($Field): int => $Field->getId(), $fields));
        self::assertContains(Fields::FIELD_TITLE, array_map(static fn ($Field): int => $Field->getId(), $fields));
    }

    public function testUniqueProductCarriesCustomerIndependentTextData(): void
    {
        $Product = $this->createTextProduct();
        $Unique = $Product->createUniqueProduct(new TestUser());

        self::assertInstanceOf(UniqueProduct::class, $Unique);
        self::assertSame(-1, $Unique->getId());
        self::assertSame('Manual service', $Unique->getTitle());
        self::assertSame('Individually described service', $Unique->getDescription());
        self::assertSame(1, $Unique->getMaximumQuantity());
        self::assertFalse($Unique->getAttribute('displayPrice'));
        self::assertSame([], $Unique->getAttribute('calculated')['vatArray']);
    }

    public function testAllPriceRepresentationsRemainZeroAndCalculationIsStable(): void
    {
        $Product = $this->createTextProduct();

        self::assertSame(0.0, $Product->getPrice()->value());
        self::assertSame(0.0, $Product->getOriginalPrice()->value());
        self::assertSame(0.0, $Product->getOfferPrice()->value());
        self::assertSame(0.0, $Product->getMinimumPrice()->value());
        self::assertSame(0.0, $Product->getMaximumPrice()->value());
        self::assertSame($Product, $Product->calc());
        $Product->resetCalculation();
    }

    public function testMissingFieldValueAndImageRaiseProductExceptions(): void
    {
        $Product = $this->createTextProduct();

        try {
            $Product->getFieldValue(999);
            self::fail('Text products do not expose arbitrary persisted field values.');
        } catch (Exception $Exception) {
            self::assertSame(1002, $Exception->getCode());
        }

        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getProject')->willReturn(null);
        QUI::$Rewrite = $Rewrite;

        $this->expectException(Exception::class);
        $Product->getImage();
    }

    private function createTextProduct(): TextProduct
    {
        return new TextProduct([
            'title' => 'Manual service',
            'description' => 'Individually described service',
            'articleNo' => 'MANUAL-01',
            'vat' => 19,
            'calculated' => ['vatArray' => [19 => 5.0]],
            'maximumQuantity' => 5
        ]);
    }
}
