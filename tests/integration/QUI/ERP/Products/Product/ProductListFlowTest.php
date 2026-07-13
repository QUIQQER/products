<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Product\UniqueProduct;
use QUITests\ERP\Products\Fixtures\RecordingCalc;
use QUITests\ERP\Products\Fixtures\TestUser;

class ProductListFlowTest extends TestCase
{
    public function testCalculationCallbackPopulatesListAndIsCached(): void
    {
        $User = new TestUser(TestUser::TYPE_NETTO);
        $List = new ProductList([], $User);
        $Calc = new RecordingCalc($User, $this->calculationData());

        self::assertSame($List, $List->calc($Calc));
        self::assertSame($List, $List->calc($Calc));
        self::assertSame(1, $Calc->calls);

        $data = $List->toArray();
        self::assertSame(119.0, $data['sum']);
        self::assertSame(100.0, $data['nettoSum']);
        self::assertSame(19.0, $data['calculations']['vatSum']);
        self::assertTrue($data['isNetto']);
    }

    public function testRecalculationInvalidatesCachedCalculation(): void
    {
        $User = new TestUser(TestUser::TYPE_NETTO);
        $List = new ProductList([], $User);
        $Calc = new RecordingCalc($User, $this->calculationData());

        $List->calc($Calc);
        $List->recalculation($Calc);

        self::assertSame(2, $Calc->calls);
    }

    public function testDuplicateModeKeepsEqualProductsAndSumsQuantities(): void
    {
        $List = new ProductList(['duplicate' => true], new TestUser());
        $List->addProduct($this->product(2));
        $List->addProduct($this->product(3));

        self::assertSame(2, $List->count());
        self::assertSame(5, $List->getQuantity());
    }

    public function testNonDuplicateModeCollapsesProductsWithEqualFields(): void
    {
        $List = new ProductList(['duplicate' => false], new TestUser());
        $List->addProduct($this->product(2));
        $List->addProduct($this->product(3));

        self::assertSame(1, $List->count());
        self::assertSame(3, $List->getQuantity());
    }

    public function testClearResetsProductsFactorsAndPriceVisibilityCanToggle(): void
    {
        $List = new ProductList([], new TestUser());
        $List->addProduct($this->product(2));
        $List->getPriceFactors()?->add(new \QUI\ERP\Products\Utils\PriceFactor(['value' => 10]));

        $List->hidePrices();
        self::assertTrue($List->isPriceHidden());

        $List->showPrices();
        self::assertFalse($List->isPriceHidden());

        $List->clear();
        self::assertSame(0, $List->count());
        self::assertSame(0, $List->getPriceFactors()?->count());
    }

    /**
     * @return UniqueProduct&MockObject
     */
    private function product(int $quantity): UniqueProduct
    {
        /** @var UniqueProduct&MockObject $Product */
        $Product = $this->getMockBuilder(UniqueProduct::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFields', 'getQuantity'])
            ->getMock();
        $Product->method('getFields')->willReturn([]);
        $Product->method('getQuantity')->willReturn($quantity);

        return $Product;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculationData(): array
    {
        return [
            'sum' => 119.0,
            'subSum' => 119.0,
            'grandSubSum' => 119.0,
            'nettoSum' => 100.0,
            'nettoSubSum' => 100.0,
            'vatArray' => [
                '19' => [
                    'vat' => 19,
                    'sum' => 19.0,
                    'text' => '19%'
                ]
            ],
            'vatText' => ['19' => ['text' => '19%']],
            'isEuVat' => false,
            'isNetto' => true,
            'currencyData' => \QUI\ERP\Defaults::getCurrency()->toArray()
        ];
    }
}
