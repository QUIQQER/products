<?php

namespace QUITests\ERP\Products\Integration\Controls;

use QUI;
use QUI\ERP\Products\Controls\Products\ProductList;
use QUI\Interfaces\Template\EngineInterface;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class ProductListTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testStringIdsRenderOnlyValidProductsOtherThanCurrentProduct(): void
    {
        $Current = ProductTestHelper::createProduct('product-list-current', 10.0);
        $Visible = ProductTestHelper::createProduct('product-list-visible', 18.5);

        foreach ([$Current, $Visible] as $Product) {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                $Product->activate($SystemUser);
            });
        }

        $assigned = [];
        $Engine = $this->createMock(EngineInterface::class);
        $Engine->method('assign')->willReturnCallback(
            static function (array | string $variables, mixed $value = false) use (&$assigned): void {
                if (is_array($variables)) {
                    $assigned = $variables;
                }
            }
        );
        $Engine->method('fetch')->willReturn('<slider-products/>');
        $Template = $this->createMock(QUI\Template::class);
        $Template->method('getEngine')->willReturn($Engine);
        $originalTemplate = QUI::$Template;
        QUI::$Template = $Template;
        $Control = new ProductList([
            'currentProductId' => $Current->getId(),
            'productIds' => implode(',', [
                $Current->getId(),
                '',
                'invalid',
                $Visible->getId(),
                999999
            ]),
            'sliderHeight' => 420
        ]);

        try {
            $html = $Control->getBody();
        } finally {
            QUI::$Template = $originalTemplate;
        }

        self::assertStringContainsString('<slider-products/>', $html);
        self::assertCount(1, $assigned['products']);
        self::assertSame($Visible->getId(), $assigned['products'][0]['Product']->getId());
        self::assertSame(18.5, $assigned['products'][0]['Price']->getAttribute('Price')->value());
        $Slider = (new ReflectionProperty(ProductList::class, 'Slider'))->getValue($Control);
        self::assertSame(420, $Slider->getAttribute('height'));
        self::assertTrue($Slider->getAttribute('data-qui-options-usemobile'));
    }

    public function testArrayIdsAreAcceptedWithoutStringConversion(): void
    {
        $Product = ProductTestHelper::createProduct('product-list-array', 6.75);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $assigned = [];
        $Engine = $this->createMock(EngineInterface::class);
        $Engine->method('assign')->willReturnCallback(
            static function (array | string $variables, mixed $value = false) use (&$assigned): void {
                if (is_array($variables)) {
                    $assigned = $variables;
                }
            }
        );
        $Engine->method('fetch')->willReturn('<slider-array/>');
        $Template = $this->createMock(QUI\Template::class);
        $Template->method('getEngine')->willReturn($Engine);
        $originalTemplate = QUI::$Template;
        QUI::$Template = $Template;

        try {
            $html = (new ProductList(['productIds' => [$Product->getId()]]))->getBody();
        } finally {
            QUI::$Template = $originalTemplate;
        }

        self::assertStringContainsString('<slider-array/>', $html);
        self::assertSame($Product->getId(), $assigned['products'][0]['Product']->getId());
    }
}
