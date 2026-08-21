<?php

namespace QUITests\ERP\Products\Integration\Utils;

use QUI;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Money\Price;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Products\Utils\Calc;
use QUI\ERP\Products\Utils\PriceFactor;
use QUI\ERP\Products\Utils\PriceFactors;
use QUITests\ERP\Products\Fixtures\TestUser;
use QUITests\ERP\Products\Integration\IntegrationTestEnvironment;

class CalcScenarioTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        IntegrationTestEnvironment::ensureDefaults();
    }

    public static function tearDownAfterClass(): void
    {
        IntegrationTestEnvironment::cleanup();
    }

    public function testNettoCustomerKeepsNetPricesAndQuantities(): void
    {
        $data = $this->calculate(new TestUser(TestUser::TYPE_NETTO), 100, 3);

        self::assertTrue($data['isNetto']);
        self::assertFalse($data['isEuVat']);
        self::assertSame(100.0, $data['nettoPrice']);
        self::assertSame(100.0, $data['price']);
        self::assertSame(300.0, $data['nettoSum']);
        self::assertSame(300.0, $data['sum']);
    }

    public function testBruttoCustomerIncludesVatAndRoundsPerUnitBeforeQuantity(): void
    {
        $data = $this->calculate(new TestUser(TestUser::TYPE_BRUTTO), 0.01, 3);

        self::assertFalse($data['isNetto']);
        self::assertGreaterThanOrEqual($data['nettoPrice'], $data['price']);
        self::assertSame($data['price'] * 3, $data['sum']);
        self::assertSame(0.03, $data['nettoSum']);
        self::assertSame($data['sum'] - $data['nettoSum'], $data['vatArray']['sum']);
    }

    public function testCompanyWithEuVatIdIsCalculatedAsNettoCustomer(): void
    {
        $data = $this->calculate(new TestUser(TestUser::TYPE_COMPANY), 100, 2);

        self::assertTrue($data['isNetto']);
        self::assertSame(100.0, $data['price']);
        self::assertSame(200.0, $data['sum']);
    }

    public function testComplementAndCurrentPricePercentageFactorsRunInOrder(): void
    {
        $Factors = new PriceFactors();
        $Factors->add(new PriceFactor([
            'title' => 'fixed fee',
            'priority' => 10,
            'calculation' => ErpCalc::CALCULATION_COMPLEMENT,
            'value' => 10
        ]));
        $Factors->add(new PriceFactor([
            'title' => 'current-price surcharge',
            'priority' => 20,
            'calculation' => ErpCalc::CALCULATION_PERCENTAGE,
            'calculation_basis' => ErpCalc::CALCULATION_BASIS_CURRENTPRICE,
            'value' => 10
        ]));

        $data = $this->calculate(new TestUser(TestUser::TYPE_NETTO), 100, 2, $Factors);

        self::assertSame(121.0, $data['nettoPrice']);
        self::assertSame(242.0, $data['nettoSum']);
        self::assertCount(2, $data['factors']);
        self::assertSame(10, $data['factors'][0]['sum']);
        self::assertSame(11.0, $data['factors'][1]['sum']);
    }

    public function testNetBasisPercentageIgnoresEarlierComplementForItsBasis(): void
    {
        $Factors = new PriceFactors();
        $Factors->add(new PriceFactor([
            'title' => 'fixed fee',
            'priority' => 10,
            'value' => 10
        ]));
        $Factors->add(new PriceFactor([
            'title' => 'base-price surcharge',
            'priority' => 20,
            'calculation' => ErpCalc::CALCULATION_PERCENTAGE,
            'calculation_basis' => ErpCalc::CALCULATION_BASIS_NETTO,
            'value' => 10
        ]));

        $data = $this->calculate(new TestUser(TestUser::TYPE_NETTO), 100, 1, $Factors);

        self::assertSame(120.0, $data['nettoPrice']);
        self::assertSame(10.0, $data['factors'][1]['sum']);
    }

    public function testCompleteFactorReplacesPriceBeforeFollowingFactors(): void
    {
        $Factors = new PriceFactors();
        $Factors->add(new PriceFactor([
            'title' => 'replacement',
            'priority' => 10,
            'calculation' => ErpCalc::CALCULATION_COMPLETE,
            'value' => 42
        ]));
        $Factors->add(new PriceFactor([
            'title' => 'fee',
            'priority' => 20,
            'value' => 8
        ]));

        $data = $this->calculate(new TestUser(TestUser::TYPE_NETTO), 100, 1, $Factors);

        self::assertSame(50.0, $data['nettoPrice']);
        self::assertCount(2, $data['factors']);
        self::assertSame(0, $data['factors'][0]['sum']);
        self::assertSame(8, $data['factors'][1]['sum']);
    }

    public function testBruttoBasisFactorsAreAppliedAfterRegularFactors(): void
    {
        $Factors = new PriceFactors();
        $Factors->add(new PriceFactor([
            'title' => 'regular fee',
            'priority' => 20,
            'value' => 10
        ]));
        $BruttoFactor = new PriceFactor([
            'title' => 'late percentage',
            'priority' => 10,
            'calculation' => ErpCalc::CALCULATION_PERCENTAGE,
            'calculation_basis' => ErpCalc::CALCULATION_BASIS_BRUTTO,
            'value' => 10
        ]);
        $Factors->add($BruttoFactor);

        $data = $this->calculate(new TestUser(TestUser::TYPE_NETTO), 100, 1, $Factors);

        self::assertSame(121.0, $data['nettoPrice']);
        self::assertSame(11.0, $BruttoFactor->getNettoSum());
    }

    public function testPriceFactorsCanBeIgnoredForBaseCalculation(): void
    {
        $Factors = new PriceFactors();
        $Factors->add(new PriceFactor([
            'title' => 'ignored fee',
            'value' => 25
        ]));

        $data = $this->calculate(
            new TestUser(TestUser::TYPE_NETTO),
            100,
            2,
            $Factors,
            true
        );

        self::assertSame(100.0, $data['nettoPrice']);
        self::assertSame(200.0, $data['nettoSum']);
        self::assertSame([], $data['factors']);
    }

    public function testDiscountCannotReduceNetPriceBelowZero(): void
    {
        $Factors = new PriceFactors();
        $Factors->add(new PriceFactor([
            'title' => 'oversized discount',
            'value' => -150
        ]));

        $data = $this->calculate(new TestUser(TestUser::TYPE_NETTO), 100, 1, $Factors);

        self::assertSame(0.0, $data['nettoPrice']);
        self::assertSame(0.0, $data['sum']);
        self::assertSame(-100.0, $data['factors'][0]['sum']);
    }

    public function testCalculatorStateAndDirectUserPricingCanBeChangedExplicitly(): void
    {
        $SessionCalc = new Calc();
        self::assertSame(QUI::getUserBySession(), $SessionCalc->getUser());

        $User = new TestUser(TestUser::TYPE_NETTO);
        $Calc = Calc::getInstance($User);
        self::assertSame($User, $Calc->getUser());
        $Replacement = new TestUser(TestUser::TYPE_BRUTTO);
        $Calc->setUser($Replacement);
        self::assertSame($Replacement, $Calc->getUser());

        $Locale = new QUI\Locale();
        $Locale->setCurrent('de');
        $Calc->setLocale($Locale);
        self::assertSame($Locale, $Calc->getLocale());
        $Calc->resetLocale();
        self::assertSame(QUI::getLocale(), $Calc->getLocale());

        $Currency = QUI\ERP\Defaults::getCurrency();
        $Calc->setCurrency($Currency);
        self::assertSame($Currency, $Calc->getCurrency());
        self::assertSame(0, $Calc->getPrice(0));
        self::assertSame(Calc::calcBruttoPrice(100), $Calc->getPrice(100));
        self::assertSame('', $Calc->getVatTextByUser());
    }

    #[DataProvider('roundTripPrices')]
    public function testDefaultVatBruttoNettoRoundTrip(float $netto): void
    {
        $brutto = Calc::calcBruttoPrice($netto);

        self::assertIsNumeric($brutto);
        self::assertEqualsWithDelta($netto, Calc::calcNettoPrice($brutto), 0.01);
    }

    public static function roundTripPrices(): iterable
    {
        yield 'small amount' => [0.01];
        yield 'regular amount' => [100.0];
        yield 'fractional amount' => [123.4567];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculate(
        TestUser $User,
        float $nettoPrice,
        int $quantity,
        ?PriceFactors $Factors = null,
        bool $ignorePriceFactors = false
    ): array {
        $Factors ??= new PriceFactors();
        $Currency = \QUI\ERP\Defaults::getCurrency();
        $Price = new Price($nettoPrice, $Currency);

        /** @var UniqueProduct&MockObject $Product */
        $Product = $this->getMockBuilder(UniqueProduct::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getNettoPrice',
                'getPriceFactors',
                'getFieldsByType',
                'getAttribute',
                'getField',
                'getQuantity',
                'getId',
                'getPrice'
            ])
            ->getMock();
        $Product->method('getNettoPrice')->willReturn($Price);
        $Product->method('getPriceFactors')->willReturn($Factors);
        $Product->method('getFieldsByType')->willReturn([]);
        $Product->method('getAttribute')->willReturn(null);
        $Product->method('getField')->willReturn(null);
        $Product->method('getQuantity')->willReturn($quantity);
        $Product->method('getId')->willReturn(9001);
        $Product->method('getPrice')->willReturn($Price);

        $result = null;
        $Calc = new Calc($User);
        $Calc->setLocale($User->getLocale());
        $Calc->setCurrency($Currency);
        $Calc->getProductPrice(
            $Product,
            static function (array $data) use (&$result): void {
                $result = $data;
            },
            null,
            $ignorePriceFactors
        );

        self::assertIsArray($result);

        return $result;
    }
}
