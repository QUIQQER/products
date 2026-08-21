<?php

namespace QUITests\ERP\Products\Integration\Controls;

use QUI;
use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Search\FrontendSearch;
use QUI\Interfaces\Template\EngineInterface;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class CategoryProductListControlTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testProductListRendersSearchResultAndFrontendOptions(): void
    {
        $Product = ProductTestHelper::createProduct('category-control-product', 15.25);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });

        self::assertSame($Product->getId(), Products::getProduct($Product->getId())->getId());

        $Control = $this->createControlWithSearch([$Product->getId()], 1, [
            'searchParams' => [
                'categories' => [ProductTestHelper::getCategory()->getId()],
                'tags' => [4, 8],
                'sortOn' => 'title',
                'sortBy' => 'ASC'
            ],
            'showCategories' => false,
            'showFilter' => false,
            'view' => 'list',
            'productLoadNumber' => 1,
            'openProductMode' => 'async'
        ]);

        $html = $Control->create();

        self::assertStringContainsString('category-control-product', $html);
        self::assertStringContainsString('data-pid="' . $Product->getId() . '"', $html);
        self::assertStringContainsString('data-categories="' . ProductTestHelper::getCategory()->getId() . '"', $html);
        self::assertStringContainsString('data-tags="4,8"', $html);
        self::assertStringContainsString('data-sort="title ASC"', $html);
        self::assertStringContainsString('data-openproductasync="1"', $html);
        self::assertSame(ProductTestHelper::getCategory()->getId(), $Control->getCategory()?->getId());
        self::assertSame(1, $Control->count());
    }

    public function testListViewReportsBothFinalAndContinuedPaginationState(): void
    {
        $Product = ProductTestHelper::createProduct('category-control-views', 23.0);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });

        self::assertSame($Product->getId(), Products::getProduct($Product->getId())->getId());

        $ListControl = $this->createControlWithSearch([$Product->getId()], 1, [
            'view' => 'list',
            'showCategories' => false
        ]);
        $listResult = $ListControl->getStart(1);

        self::assertSame(1, $listResult['count']);
        self::assertFalse($listResult['more']);
        self::assertStringContainsString('category-control-views', $listResult['html']);
        self::assertStringContainsString('quiqqer-productList-product-list', $listResult['html']);

        $ContinuedControl = $this->createControlWithSearch([$Product->getId()], 8, [
            'view' => 'list',
            'showCategories' => false,
            'productLoadNumber' => 5
        ]);
        $continuedResult = $ContinuedControl->getNext(1, 8);

        self::assertSame(8, $continuedResult['count']);
        self::assertTrue($continuedResult['more']);
        self::assertStringContainsString('category-control-views', $continuedResult['html']);
        self::assertStringContainsString('quiqqer-productList-product-list', $continuedResult['html']);
    }

    public function testFilterRenderingNormalizesSearchMetadataAndCachesTheResult(): void
    {
        $Control = $this->createControlWithSearch([], 0, [], [[
            'id' => Fields::FIELD_PRICE,
            'title' => 'PHPUnit price filter',
            'searchType' => 'range',
            'searchData' => [10, 50]
        ]]);

        $filter = $Control->getFilter();

        self::assertCount(1, $filter);
        self::assertSame(Fields::FIELD_PRICE, $filter[0]['id']);
        self::assertSame('PHPUnit price filter', $filter[0]['title']);
        self::assertSame('[10,50]', $filter[0]['searchData']);
        self::assertSame(
            Fields::getField(Fields::FIELD_PRICE)->getAttribute('priority'),
            $filter[0]['priority']
        );
        self::assertSame($filter, $Control->getFilter());

        $html = $Control->createFilter();
        self::assertStringContainsString('data-fieldid="' . Fields::FIELD_PRICE . '"', $html);
        self::assertStringContainsString('PHPUnit price filter', $html);
        self::assertStringContainsString('[10,50]', html_entity_decode($html));
    }

    public function testDetailBodyHonorsRequestedSheetAndExplicitDisplayOptions(): void
    {
        $Product = ProductTestHelper::createProduct('category-control-detail', 27.5);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Control = $this->createControlWithSearch([$Product->getId()], 7, [
            'categoryPos' => 'false',
            'productLoadNumber' => false,
            'autoloadAfter' => false,
            'openProductMode' => 'normal',
            'view' => 'detail',
            'categoryView' => 'list',
            'showCategories' => true,
            'searchParams' => [
                'fields' => [Fields::FIELD_PRICE => ['min' => 20, 'max' => 30]]
            ]
        ]);
        $Control->addSort('Price ascending', Fields::FIELD_PRICE . ' ASC');
        $originalRequest = $_REQUEST;
        $_REQUEST['sheet'] = 2;

        try {
            $html = $Control->create();
        } finally {
            $_REQUEST = $originalRequest;
        }

        self::assertStringNotContainsString('category-control-detail', $html);
        self::assertStringContainsString('Price ascending', $html);
        self::assertSame('detail', $Control->getAttribute('data-qui-options-view'));
        self::assertSame(0, $Control->getAttribute('data-openproductasync'));
        self::assertFalse($Control->getAttribute('data-productLoadNumber'));
        self::assertFalse($Control->getAttribute('data-autoloadAfter'));
    }

    public function testGalleryRenderingCountsArrayResultAndIgnoresMissingProducts(): void
    {
        $Product = ProductTestHelper::createProduct('category-control-gallery', 14.0);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $CountControl = $this->createControlWithSearch(
            [],
            [$Product->getId(), 999999],
            ['view' => 'gallery', 'showCategories' => false]
        );

        self::assertSame(2, $CountControl->count());

        self::assertSame($Product->getId(), Products::getProduct($Product->getId())->getId());
        $Control = $this->createControlWithSearch(
            [$Product->getId(), 999999],
            2,
            ['view' => 'gallery', 'showCategories' => false]
        );
        $assigned = [];
        $Engine = $this->createMock(EngineInterface::class);
        $Engine->expects(self::once())->method('assign')->willReturnCallback(
            static function (array $variables) use (&$assigned): void {
                $assigned = $variables;
            }
        );
        $Engine->expects(self::once())->method('fetch')->with(
            dirname(__DIR__, 6) . '/src/QUI/ERP/Products/Controls/Category/ProductListRow.html'
        )->willReturn('<gallery-row/>');
        $Template = $this->createMock(QUI\Template::class);
        $Template->method('getEngine')->willReturn($Engine);
        $originalTemplate = QUI::$Template;
        QUI::$Template = $Template;

        try {
            $result = $Control->getStart();
        } finally {
            QUI::$Template = $originalTemplate;
        }

        self::assertSame(2, $result['count']);
        self::assertFalse($result['more']);
        self::assertSame('<gallery-row/>', $result['html']);
        self::assertSame([$Product->getId()], array_map(
            static fn ($Entry): int => $Entry->getId(),
            $assigned['products']
        ));
        self::assertSame(
            dirname(__DIR__, 6) . '/src/QUI/ERP/Products/Controls/Category/ProductListGallery.html',
            $assigned['productTpl']
        );
    }

    public function testProductAndEmptyCategoryTemplatesCanBeRenderedDirectly(): void
    {
        $Product = ProductTestHelper::createProduct('category-control-direct-render', 40.0);
        $Product->getField(Fields::FIELD_PRICE_OFFER)->setValue(32.0);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Control = $this->createControlWithSearch([], 0, [
            'categoryStartNumber' => 2
        ]);

        $productHtml = $Control->renderProduct(
            $Product,
            dirname(__DIR__, 6) . '/src/QUI/ERP/Products/Controls/Category/ProductListList.html'
        );
        self::assertStringContainsString('category-control-direct-render', $productHtml);

        $categoryHtml = $Control->renderCategories(
            [],
            dirname(__DIR__, 6) . '/src/QUI/ERP/Products/Controls/Category/ProductListCategoryGallery.html'
        );
        self::assertStringContainsString('quiqqer-products-categoryGallery-categories', $categoryHtml);

        $OldPrice = $Control->getProductOldPriceDisplay($Product->getView());
        self::assertNotNull($OldPrice);
        self::assertStringContainsString('40', $OldPrice->create());
    }

    /**
     * @param int[] $productIds
     * @param int|int[] $count
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>> $fieldData
     */
    private function createControlWithSearch(
        array $productIds,
        int | array $count,
        array $attributes,
        array $fieldData = []
    ): ProductList {
        $Search = $this->createMock(FrontendSearch::class);
        $Search->method('search')->willReturnCallback(
            static fn (array $params, bool $countOnly = false): array | int => $countOnly ? $count : $productIds
        );
        $Search->method('getSearchFieldData')->willReturn($fieldData);

        $Control = new ProductList(array_merge([
            'Project' => ProductTestHelper::getProject(),
            'Site' => ProductTestHelper::getCategorySite()
        ], $attributes));

        (new ReflectionProperty(ProductList::class, 'Search'))->setValue($Control, $Search);

        return $Control;
    }
}
