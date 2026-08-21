<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI\ERP\Products\Handler\Fields;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class CalculationAjaxTest extends AjaxTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testBruttoAndNettoEndpointsRoundTripProductPriceAndFormatResults(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-price-conversion', 100);
        $gross = $this->invokeEndpoint(
            'products/calcBruttoPrice.php',
            'package_quiqqer_products_ajax_products_calcBruttoPrice',
            100,
            false,
            $Product->getId()
        );
        self::assertIsNumeric($gross);
        self::assertGreaterThanOrEqual(100, $gross);

        $net = $this->invokeEndpoint(
            'products/calcNettoPrice.php',
            'package_quiqqer_products_ajax_products_calcNettoPrice',
            $gross,
            false,
            $Product->getId()
        );
        self::assertEqualsWithDelta(100.0, (float)$net, 0.01);

        $grossFormatted = $this->invokeEndpoint(
            'products/calcBruttoPrice.php',
            'package_quiqqer_products_ajax_products_calcBruttoPrice',
            '100,00',
            true,
            $Product->getId()
        );
        $netFormatted = $this->invokeEndpoint(
            'products/calcNettoPrice.php',
            'package_quiqqer_products_ajax_products_calcNettoPrice',
            $gross,
            true,
            $Product->getId()
        );
        self::assertIsString($grossFormatted);
        self::assertNotSame('', $grossFormatted);
        self::assertIsString($netFormatted);
        self::assertNotSame('', $netFormatted);
    }

    public function testProductCalculationUsesQuantityAndIgnoresInvalidFieldSelections(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-product-calculation', 25);
        $fields = json_encode([
            [],
            [
                'fieldId' => Fields::FIELD_PRICE,
                'value' => 999
            ],
            [
                'fieldId' => 999999,
                'value' => 'missing'
            ]
        ], JSON_THROW_ON_ERROR);

        $result = $this->invokeEndpoint(
            'products/calc.php',
            'package_quiqqer_products_ajax_products_calc',
            $Product->getId(),
            $fields,
            2
        );

        self::assertSame($Product->getId(), $result['id']);
        self::assertSame(2.0, $result['quantity']);
        self::assertSame(25.0, $result['calculated_nettoPriceNotRounded']);
        self::assertSame(50.0, $result['calculated_nettoSum']);

        $defaultQuantity = $this->invokeEndpoint(
            'products/calc.php',
            'package_quiqqer_products_ajax_products_calc',
            $Product->getId(),
            'invalid-json',
            0
        );
        self::assertSame(1.0, $defaultQuantity['quantity']);
        self::assertSame(25.0, $defaultQuantity['calculated_nettoSum']);
    }
}
