<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Products\Field\Types\ProductAttributeListBackendView;
use QUI\ERP\Products\Field\Types\ProductAttributeListFrontendView;

class ProductAttributeListViewTest extends TestCase
{
    public function testFrontendViewRendersSelectionRequirementAndPriceAdjustment(): void
    {
        $View = new ProductAttributeListFrontendView($this->getViewData('[1,"engraving"]'));

        $html = $View->create();

        self::assertStringContainsString('data-field="9202"', $html);
        self::assertStringContainsString('name="field-9202"', $html);
        self::assertStringContainsString('required="required"', $html);
        self::assertStringContainsString('value="1" selected="selected"', $html);
        self::assertStringContainsString('Premium (+10%)', $html);
        self::assertStringContainsString('data-userinput="1"', $html);
        self::assertStringContainsString('Disabled option', $html);
        self::assertStringContainsString('disabled="disabled"', $html);
    }

    public function testFrontendViewRendersFixedSelectionAsText(): void
    {
        $data = $this->getViewData(1);
        $data['changeable'] = false;
        $View = new ProductAttributeListFrontendView($data);

        $html = $View->create();

        self::assertStringContainsString('[quiqqer/products] products.field.9202.title:', $html);
        self::assertStringContainsString('Premium', $html);
        self::assertStringNotContainsString('<select', $html);
    }

    public function testBackendViewRendersPersistedValueAndAvailableOptions(): void
    {
        $View = new ProductAttributeListBackendView($this->getViewData(1));

        $html = $View->create();

        self::assertStringContainsString('backendView/ProductAttributeList', $html);
        self::assertStringContainsString('value="1" selected="selected"', $html);
        self::assertStringContainsString('Premium (+10%)', $html);
        self::assertStringContainsString('data-userinput="1"', $html);
        self::assertStringContainsString('required="required"', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(mixed $value): array
    {
        return [
            'id' => 9202,
            'title' => 'Attribute choice',
            'type' => 'ProductAttributeList',
            'value' => $value,
            'isPublic' => true,
            'isRequired' => true,
            'changeable' => true,
            'options' => [
                'display_discounts' => true,
                'entries' => [
                    [
                        'title' => ['de' => 'Standard'],
                        'sum' => 0,
                        'type' => ErpCalc::CALCULATION_COMPLEMENT
                    ],
                    [
                        'title' => ['de' => 'Premium'],
                        'sum' => 10,
                        'type' => ErpCalc::CALCULATION_PERCENTAGE,
                        'userinput' => true
                    ],
                    [
                        'title' => 'Disabled option',
                        'sum' => 5,
                        'type' => ErpCalc::CALCULATION_COMPLEMENT,
                        'disabled' => true
                    ]
                ]
            ]
        ];
    }
}
