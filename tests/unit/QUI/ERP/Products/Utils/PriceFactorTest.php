<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Accounting\Calc;
use QUI\ERP\Products\Utils\PriceFactor;

class PriceFactorTest extends TestCase
{
    public function testConstructorMapsCalculationAttributes(): void
    {
        $Factor = new PriceFactor([
            'identifier' => 'shipping',
            'title' => 'Shipping',
            'description' => 'Express delivery',
            'priority' => 12,
            'calculation' => Calc::CALCULATION_PERCENTAGE,
            'calculation_basis' => Calc::CALCULATION_BASIS_CURRENTPRICE,
            'value' => '7.5',
            'valueText' => 'custom',
            'visible' => false,
            'vat' => 19,
            'sum' => 9.5,
            'nettoSum' => 8.0
        ]);

        self::assertSame('shipping', $Factor->getIdentifier());
        self::assertSame('Shipping', $Factor->getTitle());
        self::assertSame('Express delivery', $Factor->getDescription());
        self::assertSame(12, $Factor->getPriority());
        self::assertSame(Calc::CALCULATION_PERCENTAGE, $Factor->getCalculation());
        self::assertSame(Calc::CALCULATION_BASIS_CURRENTPRICE, $Factor->getCalculationBasis());
        self::assertSame(7.5, $Factor->getValue());
        self::assertSame('custom', $Factor->getValueText());
        self::assertFalse($Factor->isVisible());
        self::assertSame(19, $Factor->getVat());
        self::assertSame(9.5, $Factor->getSum());
        self::assertSame(8.0, $Factor->getNettoSum());
    }

    public function testInvalidCalculationValuesKeepDefaults(): void
    {
        $Factor = new PriceFactor();
        $Factor->setCalculation(9999);
        $Factor->setCalculationBasis(9999);

        self::assertSame(Calc::CALCULATION_COMPLEMENT, $Factor->getCalculation());
        self::assertSame(Calc::CALCULATION_BASIS_NETTO, $Factor->getCalculationBasis());
    }

    public function testBruttoCalculationBasisIsAccepted(): void
    {
        $Factor = new PriceFactor();

        $Factor->setCalculationBasis(Calc::CALCULATION_BASIS_BRUTTO);

        self::assertSame(Calc::CALCULATION_BASIS_BRUTTO, $Factor->getCalculationBasis());
    }

    public function testPercentageValueTextUsesPercentageNotation(): void
    {
        $Factor = new PriceFactor([
            'calculation' => Calc::CALCULATION_PERCENTAGE,
            'value' => 12.5
        ]);

        self::assertSame('12.5%', $Factor->getValueText());
        self::assertFalse($Factor->hasValueText());
    }

    public function testExplicitEmptyValueTextUsesPlaceholder(): void
    {
        $Factor = new PriceFactor(['valueText' => '']);

        self::assertSame('-', $Factor->getValueText());
        self::assertFalse($Factor->hasValueText());
    }

    public function testArrayRepresentationContainsStableCalculationData(): void
    {
        $Factor = new PriceFactor([
            'identifier' => 'discount',
            'title' => 'Discount',
            'value' => -10,
            'sum' => -10,
            'nettoSum' => -10,
            'vat' => 7
        ]);

        $data = $Factor->toArray();

        self::assertSame('discount', $data['identifier']);
        self::assertSame('Discount', $data['title']);
        self::assertSame(-10, $data['value']);
        self::assertSame(-10, $data['sum']);
        self::assertSame(-10, $data['nettoSum']);
        self::assertSame(7, $data['vat']);
        self::assertSame(PriceFactor::class, $data['class']);
        self::assertSame($Factor->getCurrency()->getCode(), $data['currency']);
    }

    public function testConversionToErpFactorPreservesBusinessValues(): void
    {
        $Factor = new PriceFactor([
            'identifier' => 'fee',
            'title' => 'Fee',
            'description' => 'Handling',
            'value' => 4.25,
            'sum' => 8.5,
            'nettoSum' => 8.5,
            'visible' => true
        ]);

        $data = $Factor->toErpPriceFactor()->toArray();

        self::assertSame('fee', $data['identifier']);
        self::assertSame('Fee', $data['title']);
        self::assertSame('Handling', $data['description']);
        self::assertSame(4.25, $data['value']);
        self::assertSame(8.5, $data['sum']);
        self::assertSame(8.5, $data['nettoSum']);
    }
}
