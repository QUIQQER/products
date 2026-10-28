<?php

namespace QUITests\ERP\Products\Integration\Controls;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Search\Cache;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class CategoryProductListSearchTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    /**
     * @param list<list<int>> $assignments Additional categories per product, indexed into the test category list.
     * @param list<int> $expectedIndexes
     */
    #[DataProvider('categorySearches')]
    public function testCountAndRenderedPagesUseTheSameCategoryAndFieldFilters(
        string $searchType,
        array $assignments,
        array $expectedIndexes,
        bool $filterByPrice = false
    ): void {
        $extraCategories = [];

        try {
            foreach (['a', 'b'] as $suffix) {
                $extraCategories[] = ProductTestHelper::runAsSystemUser(
                    static fn () => Categories::createCategory(0, ProductTestHelper::PREFIX . 'search-' . $suffix)
                );
            }

            $productIds = [];

            foreach ($assignments as $index => $categoryIndexes) {
                $Product = ProductTestHelper::createProduct(
                    ProductTestHelper::PREFIX . 'category-search-' . $index,
                    ($index + 1) * 10
                );
                ProductTestHelper::runAsSystemUser(
                    static function ($User) use ($Product, $categoryIndexes, $extraCategories): void {
                        foreach ($categoryIndexes as $categoryIndex) {
                            $Product->addCategory($extraCategories[$categoryIndex]);
                        }

                        $Product->save($User);
                        $Product->activate($User);
                    }
                );
                $productIds[] = $Product->getId();
            }

            Cache::clear();
            $Site = ProductTestHelper::getCategorySite();
            $Site->setAttribute(
                'quiqqer.products.settings.extraProductCategories',
                implode(',', array_map(static fn ($Category) => $Category->getId(), $extraCategories))
            );
            $searchParams = ['sortOn' => 'id', 'sortBy' => 'ASC'];

            if ($filterByPrice) {
                $searchParams['fields'] = [Fields::FIELD_PRICE => ['from' => 20, 'to' => 30]];
            }

            $Control = new ProductList([
                'Project' => ProductTestHelper::getProject(),
                'Site' => $Site,
                'categoryProductSearchType' => $searchType,
                'searchParams' => $searchParams,
                'productLoadNumber' => 3,
                'showCategories' => false,
                'showFilter' => false
            ]);
            $expectedIds = array_map(static fn ($index) => $productIds[$index], $expectedIndexes);
            $expectedCount = count($expectedIds);

            self::assertSame($expectedCount, $Control->count());
            $firstPage = $Control->getStart();
            self::assertSame($expectedCount, $firstPage['count']);
            self::assertSame(array_slice($expectedIds, 0, 3), $firstPage['productIds']);
            self::assertSame($expectedCount > 3, $firstPage['more']);

            if ($expectedCount > 3) {
                $nextPage = $Control->getNext(3);
                self::assertSame($expectedCount, $nextPage['count']);
                self::assertSame(array_slice($expectedIds, 3), $nextPage['productIds']);
                self::assertFalse($nextPage['more']);
            }
        } finally {
            ProductTestHelper::cleanupProducts();

            foreach ($extraCategories as $Category) {
                ProductTestHelper::runAsSystemUser(static fn () => $Category->delete());
            }

            Cache::clear();
        }
    }

    public static function categorySearches(): iterable
    {
        yield 'AND excludes a main-category-only product' => ['AND', [[]], []];
        yield 'AND requires every additional category' => ['AND', [[], [0], [1]], []];
        yield 'AND includes a product with all categories' => ['AND', [[], [0], [1], [0, 1]], [3]];
        yield 'OR count ignores pagination' => ['OR', [[], [0], [1], [0, 1]], [0, 1, 2, 3]];
        yield 'AND combines category and price filters' => ['AND', [[], [0], [1], [0, 1]], [], true];
        yield 'OR combines category and price filters' => ['OR', [[], [0], [1], [0, 1]], [1, 2], true];
    }
}
