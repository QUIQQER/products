<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Utils\PriceFactor;
use QUI\ERP\Products\Utils\PriceFactors;

class PriceFactorsTest extends TestCase
{
    public function testSortPreservesSectionsAndOrdersWithinEachSection(): void
    {
        $Factors = new PriceFactors();
        $Factors->addToBeginning($this->factor('begin-late', 20));
        $Factors->addToBeginning($this->factor('begin-early', 10));
        $Factors->add($this->factor('middle-z', 10));
        $Factors->add($this->factor('middle-a', 10));
        $Factors->addToEnd($this->factor('end', 0));

        self::assertSame(
            ['begin-early', 'begin-late', 'middle-a', 'middle-z', 'end'],
            array_map(static fn (PriceFactor $Factor): string => $Factor->getTitle(), $Factors->sort())
        );
        self::assertSame(5, $Factors->count());
    }

    public function testClearRemovesEverySection(): void
    {
        $Factors = new PriceFactors();
        $Factors->addToBeginning($this->factor('begin', 0));
        $Factors->add($this->factor('middle', 0));
        $Factors->addToEnd($this->factor('end', 0));

        $Factors->clear();

        self::assertSame(0, $Factors->count());
        self::assertSame([], $Factors->getFactors());
        self::assertSame(['beginning' => [], 'middle' => [], 'end' => []], $Factors->toArray());
    }

    public function testExportImportRoundTripPreservesSectionsAndValues(): void
    {
        $Original = new PriceFactors();
        $Original->addToBeginning($this->factor('begin', 2, -5));
        $Original->add($this->factor('middle', 1, 10));
        $Original->addToEnd($this->factor('end', 3, 2.5));

        $Imported = new PriceFactors();
        $Imported->importList($Original->toArray());

        $expected = $Original->toArray();

        foreach (['beginning', 'middle', 'end'] as $section) {
            foreach ($expected[$section] as &$factor) {
                $factor['vat'] = 0;
            }
            unset($factor);
        }

        self::assertSame($expected, $Imported->toArray());
        self::assertJson($Imported->toJSON());
        self::assertSame($Imported->toArray(), json_decode($Imported->toJSON(), true));
    }

    public function testImportWithoutKnownSectionsDoesNothing(): void
    {
        $Factors = new PriceFactors();
        $Factors->add($this->factor('existing', 1));

        $Factors->importList(['unknown' => []]);

        self::assertSame(1, $Factors->count());
        self::assertSame('existing', $Factors->sort()[0]->getTitle());
    }

    public function testErpListUsesSortedOrder(): void
    {
        $Factors = new PriceFactors();
        $Factors->add($this->factor('second', 20));
        $Factors->add($this->factor('first', 10));

        $titles = [];

        foreach ($Factors->toErpPriceFactorList() as $Factor) {
            $titles[] = $Factor->getTitle();
        }

        self::assertSame(['first', 'second'], $titles);
    }

    private function factor(string $title, int $priority, float|int $value = 1): PriceFactor
    {
        return new PriceFactor([
            'title' => $title,
            'description' => $title,
            'priority' => $priority,
            'value' => $value,
            'sum' => $value,
            'nettoSum' => $value
        ]);
    }
}
