<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Products\Utils\Calc;
use QUITests\ERP\Products\Fixtures\TestUser;

class CalcTest extends TestCase
{
    public function testConstructorAndUserSetterKeepExplicitUser(): void
    {
        $BruttoUser = new TestUser(TestUser::TYPE_BRUTTO);
        $NettoUser = new TestUser(TestUser::TYPE_NETTO);
        $Calc = new Calc($BruttoUser);

        self::assertSame($BruttoUser, $Calc->getUser());

        $Calc->setUser($NettoUser);

        self::assertSame($NettoUser, $Calc->getUser());
    }

    public function testLocaleCanBeSetAndReset(): void
    {
        $Calc = new Calc(new TestUser());
        $Locale = new QUI\Locale();
        $Locale->setCurrent('en');

        $Calc->setLocale($Locale);
        self::assertSame($Locale, $Calc->getLocale());

        $Calc->resetLocale();
        self::assertSame(QUI::getLocale(), $Calc->getLocale());
    }

    public function testZeroPriceShortCircuitsWithoutTaxLookup(): void
    {
        self::assertSame(0, (new Calc(new TestUser()))->getPrice(0));
    }

    public function testDeprecatedConstantsRemainAliasesForErpConstants(): void
    {
        self::assertSame(ErpCalc::CALCULATION_PERCENTAGE, Calc::CALCULATION_PERCENTAGE);
        self::assertSame(ErpCalc::CALCULATION_COMPLEMENT, Calc::CALCULATION_COMPLEMENT);
        self::assertSame(ErpCalc::CALCULATION_BASIS_NETTO, Calc::CALCULATION_BASIS_NETTO);
        self::assertSame(ErpCalc::CALCULATION_BASIS_CURRENTPRICE, Calc::CALCULATION_BASIS_CURRENTPRICE);
        self::assertSame(ErpCalc::CALCULATION_BASIS_BRUTTO, Calc::CALCULATION_BASIS_BRUTTO);
    }
}
