<?php

namespace QUITests\ERP\Products\Integration\Utils;

use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Utils\Calc;
use QUI\ERP\Products\Utils\PriceFactor;
use QUITests\ERP\Products\Fixtures\TestUser;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class CalcProductListIntegrationTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testProductListCalculatesProductsAndOrderedPriceFactors(): void
    {
        $User = new TestUser(TestUser::TYPE_NETTO);
        $First = ProductTestHelper::createProduct('calculation-first', 100)
            ->createUniqueProduct($User);
        $Second = ProductTestHelper::createProduct('calculation-second', 50)
            ->createUniqueProduct($User);
        $First->setQuantity(2);

        $List = new ProductList([], $User);
        $List->addProduct($First);
        $List->addProduct($Second);
        $List->getPriceFactors()->add(new PriceFactor([
            'title' => 'fixed handling',
            'priority' => 10,
            'calculation' => ErpCalc::CALCULATION_COMPLEMENT,
            'value' => 10
        ]));
        $List->getPriceFactors()->add(new PriceFactor([
            'title' => 'net surcharge',
            'priority' => 20,
            'calculation' => ErpCalc::CALCULATION_PERCENTAGE,
            'calculation_basis' => ErpCalc::CALCULATION_BASIS_NETTO,
            'value' => 10
        ]));
        $List->getPriceFactors()->add(new PriceFactor([
            'title' => 'final correction',
            'priority' => 30,
            'calculation_basis' => ErpCalc::CALCULATION_GRAND_TOTAL,
            'value' => -5
        ]));

        $result = null;
        $Calculated = (new Calc($User))->calcProductList(
            $List,
            static function (array $data) use (&$result): void {
                $result = $data;
            }
        );

        self::assertSame($List, $Calculated);
        self::assertIsArray($result);
        self::assertSame(250.0, $result['subSum']);
        self::assertSame(250.0, $result['nettoSubSum']);
        self::assertSame(285.0, $result['nettoSum']);
        self::assertSame(285.0, $result['grandSubSum']);
        self::assertSame(280.0, $result['sum']);
        self::assertTrue($result['isNetto']);
        self::assertFalse($result['isEuVat']);
        self::assertSame([], $result['vatArray']);
        self::assertSame([], $result['vatText']);
        self::assertSame(
            \QUI\ERP\Defaults::getCurrency()->getCode(),
            $result['currencyData']['code']
        );
    }

    public function testIgnoringVatTreatsBruttoCustomerAsNetto(): void
    {
        $User = new TestUser(TestUser::TYPE_BRUTTO);
        $Product = ProductTestHelper::createProduct('calculation-without-vat', 80)
            ->createUniqueProduct($User);
        $List = new ProductList([], $User);
        $List->addProduct($Product);
        $result = null;
        $Calc = new Calc($User);
        $Calc->ignoreVatCalculation();

        $Calc->calcProductList(
            $List,
            static function (array $data) use (&$result): void {
                $result = $data;
            }
        );

        self::assertIsArray($result);
        self::assertTrue($result['isNetto']);
        self::assertSame(80.0, $result['nettoSum']);
        self::assertSame(80.0, $result['sum']);
        self::assertSame([], $result['vatArray']);
        self::assertSame([], $result['vatText']);
    }

    public function testBruttoListFactorsExposeVatAdjustedSumsAndDisplayValues(): void
    {
        $User = new TestUser(TestUser::TYPE_BRUTTO);
        $Product = ProductTestHelper::createProduct('gross-list-factors', 100)
            ->createUniqueProduct($User);
        $List = new ProductList([], $User);
        $List->addProduct($Product);
        $Fee = new PriceFactor([
            'title' => 'gross handling fee',
            'priority' => 10,
            'calculation' => ErpCalc::CALCULATION_COMPLEMENT,
            'value' => 10
        ]);
        $GrossPercentage = new PriceFactor([
            'title' => 'gross basis surcharge',
            'priority' => 20,
            'calculation' => ErpCalc::CALCULATION_PERCENTAGE,
            'calculation_basis' => ErpCalc::CALCULATION_BASIS_VAT_BRUTTO,
            'value' => 10
        ]);
        $Fee->setVat(19);
        $GrossPercentage->setVat(19);
        $List->getPriceFactors()->add($Fee);
        $List->getPriceFactors()->add($GrossPercentage);
        $result = null;

        (new Calc($User))->calcProductList(
            $List,
            static function (array $data) use (&$result): void {
                $result = $data;
            }
        );

        self::assertIsArray($result);
        self::assertFalse($result['isNetto']);
        self::assertGreaterThan($result['nettoSum'], $result['sum']);
        self::assertSame(10.0, $Fee->getNettoSum());
        self::assertSame(11.9, $Fee->getSum());
        self::assertTrue($Fee->hasValueText());
        self::assertEqualsWithDelta(8.40336, $GrossPercentage->getNettoSum(), 0.00001);
        self::assertEqualsWithDelta(10.00336, $GrossPercentage->getSum(), 0.00001);
        self::assertSame(19, $result['vatArray'][19]['vat']);
        self::assertTrue($result['vatArray'][19]['visible']);
        self::assertEqualsWithDelta(3.49664, $result['vatArray'][19]['sum'], 0.00001);
        self::assertSame($result['vatArray'][19]['text'], $result['vatText'][19]);
    }

    public function testOversizedListDiscountCannotProduceNegativeTotals(): void
    {
        $User = new TestUser(TestUser::TYPE_BRUTTO);
        $Product = ProductTestHelper::createProduct('gross-list-discount', 25)
            ->createUniqueProduct($User);
        $List = new ProductList([], $User);
        $List->addProduct($Product);
        $Discount = new PriceFactor([
            'title' => 'oversized list discount',
            'calculation' => ErpCalc::CALCULATION_COMPLEMENT,
            'value' => -500
        ]);
        $List->getPriceFactors()->add($Discount);
        $result = null;

        (new Calc($User))->calcProductList(
            $List,
            static function (array $data) use (&$result): void {
                $result = $data;
            }
        );

        self::assertIsArray($result);
        self::assertSame(0, $result['nettoSum']);
        self::assertSame(0, $result['sum']);
        self::assertLessThanOrEqual(0, $Discount->getNettoSum());
        foreach ($result['vatArray'] as $vat) {
            self::assertSame(0, $vat['sum']);
        }
    }

    public function testStaticProductVatConversionsSupportFormattingAndZero(): void
    {
        $Product = ProductTestHelper::createProduct('static-vat-conversion', 100);

        $gross = Calc::calcBruttoPrice(100, false, $Product->getId());
        $grossFormatted = Calc::calcBruttoPrice(100, true, $Product->getId());
        $net = Calc::calcNettoPrice($gross, false, $Product->getId());
        $netFormatted = Calc::calcNettoPrice($gross, true, $Product->getId());

        self::assertIsNumeric($gross);
        self::assertIsString($grossFormatted);
        self::assertNotSame('', $grossFormatted);
        self::assertEqualsWithDelta(100.0, (float)$net, 0.01);
        self::assertIsString($netFormatted);
        self::assertNotSame('', $netFormatted);
        self::assertSame(0, Calc::calcNettoPrice(null));
    }

    public function testListWithoutCallbackUsesItsOwnCalculationContract(): void
    {
        $User = new TestUser(TestUser::TYPE_NETTO);
        $List = new ProductList([], $User);

        self::assertSame($List, (new Calc($User))->calcProductList($List));
    }
}
