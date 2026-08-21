<?php

namespace QUITests\ERP\Products\Integration\Controls;

use QUI;
use QUI\ERP\Products\Controls\Products\ProductEdit;
use QUI\Interfaces\Template\EngineInterface;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductEditTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testProductIsMappedToEditableViewAndCalculatedPrice(): void
    {
        $Product = ProductTestHelper::createProduct('product-edit-model', 25.0);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $assigned = [];
        [$Template, $Engine] = $this->createTemplateBoundary($assigned, '<product-edit-model/>');
        $originalTemplate = QUI::$Template;
        QUI::$Template = $Template;

        try {
            $Control = new ProductEdit(['Product' => $Product]);
            self::assertSame('<product-edit-model/>', $Control->getBody());
        } finally {
            QUI::$Template = $originalTemplate;
        }

        self::assertSame($Product->getId(), $assigned['Product']->getId());
        self::assertSame(25.0, $assigned['Price']->value());
        self::assertSame([], $assigned['customFields']);
        self::assertIsArray($assigned['productAttributeList']);
        self::assertSame(
            dirname(__DIR__, 6) . '/src/QUI/ERP/Products/Controls/Products/ProductEdit.html',
            $Engine->getTemplateVariable('fetchedTemplate')
        );
    }

    public function testExistingProductViewIsUsedWithoutRemapping(): void
    {
        $Product = ProductTestHelper::createProduct('product-edit-view', 9.5);
        $View = $Product->getViewBackend();
        $assigned = [];
        [$Template] = $this->createTemplateBoundary($assigned, '<product-edit-view/>');
        $originalTemplate = QUI::$Template;
        QUI::$Template = $Template;

        try {
            self::assertSame(
                '<product-edit-view/>',
                (new ProductEdit(['Product' => $View]))->getBody()
            );
        } finally {
            QUI::$Template = $originalTemplate;
        }

        self::assertSame($View, $assigned['Product']);
        self::assertSame(9.5, $assigned['Price']->value());
        self::assertSame([], $assigned['customFields']);
    }

    /**
     * @param array<string, mixed> $assigned
     * @return array{QUI\Template, EngineInterface}
     */
    private function createTemplateBoundary(array &$assigned, string $result): array
    {
        $fetchedTemplate = null;
        $Engine = $this->createMock(EngineInterface::class);
        $Engine->method('assign')->willReturnCallback(
            static function (array | string $variables, mixed $value = false) use (&$assigned): void {
                if (is_array($variables)) {
                    $assigned = $variables;
                }
            }
        );
        $Engine->method('fetch')->willReturnCallback(
            static function (string $template) use (&$fetchedTemplate, $result): string {
                $fetchedTemplate = $template;

                return $result;
            }
        );
        $Engine->method('getTemplateVariable')->willReturnCallback(
            static function (string $name) use (&$fetchedTemplate): mixed {
                return $name === 'fetchedTemplate' ? $fetchedTemplate : null;
            }
        );
        $Template = $this->createMock(QUI\Template::class);
        $Template->method('getEngine')->willReturn($Engine);

        return [$Template, $Engine];
    }
}
