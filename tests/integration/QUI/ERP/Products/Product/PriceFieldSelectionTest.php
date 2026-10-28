<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

class PriceFieldSelectionTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    #[DataProvider('priceOptions')]
    public function testExcludedCheaperFieldDoesNotDetermineTheFrontendPrice(bool $excluded, float $expected): void
    {
        $Product = ProductTestHelper::createProduct(ProductTestHelper::PREFIX . 'price-field-selection', 100);

        foreach ([1805 => [80, false], 1806 => [5, $excluded]] as $id => [$price, $ignore]) {
            $Field = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
                'id' => $id,
                'type' => Fields::TYPE_PRICE,
                'name' => 'PHPUnit price exclusion ' . $id,
                'publicField' => 1,
                'options' => ['ignoreForPriceCalculation' => $ignore]
            ]));
            $Field->setOwnFieldStatus(true);
            $Field->setValue($price);
            $Product->addField($Field);
        }

        ProductTestHelper::runAsSystemUser(static function ($User) use ($Product): void {
            $Product->save($User);
            $Product->activate($User);
        });

        Products::cleanProductInstanceMemCache($Product->getId());
        $Product = Products::getNewProductInstance($Product->getId());

        self::assertSame(5.0, $Product->getFieldValue(1806));
        self::assertSame($excluded, (bool)$Product->getField(1806)->getOption('ignoreForPriceCalculation'));
        self::assertSame($expected, $Product->getPrice()->value());
        self::assertSame($expected, $Product->createUniqueProduct()->getPrice()->value());
        self::assertSame($expected, $Product->getCurrentPrice()->value());
        self::assertSame($expected, $Product->getViewFrontend()->getPrice()->value());
    }

    public static function priceOptions(): iterable
    {
        yield 'excluded five-euro field leaves the eighty-euro field selected' => [true, 80.0];
        yield 'including the five-euro field makes it the selected price' => [false, 5.0];
    }
}
