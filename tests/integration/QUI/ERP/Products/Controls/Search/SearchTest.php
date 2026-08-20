<?php

namespace QUITests\ERP\Products\Integration\Controls\Search;

use QUI\ERP\Products\Controls\Search\Search;
use QUI\ERP\Products\Search\FrontendSearch;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class SearchTest extends ProductIntegrationTestCase
{
    public function testConfiguredFieldsRenderOnceAndExposeProjectContext(): void
    {
        $FrontendSearch = $this->createMock(FrontendSearch::class);
        $FrontendSearch->expects(self::once())->method('getSearchFieldData')->willReturn([
            ['id' => 4, 'type' => 'text'],
            ['id' => 17, 'type' => 'selectRange']
        ]);
        $Control = new Search([
            'Site' => ProductTestHelper::getCategorySite(),
            'data-name' => 'product-filter'
        ]);
        (new ReflectionProperty(Search::class, 'Search'))->setValue($Control, $FrontendSearch);

        self::assertTrue($Control->hasFields());
        self::assertTrue($Control->hasFields());
        $html = $Control->create();

        self::assertStringContainsString('<form>', $html);
        self::assertSame(3, substr_count($html, 'quiqqer-products-search-fieldplaceholder-select'));
        self::assertSame(ProductTestHelper::getProject()->getName(), $Control->getAttribute('data-project'));
        self::assertSame(ProductTestHelper::getProject()->getLang(), $Control->getAttribute('data-lang'));
        self::assertSame(ProductTestHelper::getCategorySite()->getId(), $Control->getAttribute('data-siteid'));
        self::assertSame('product-filter', $Control->getAttribute('data-name'));
    }

    public function testEmptyFieldsAndDisabledFreeTextRenderEmptyFormBody(): void
    {
        $FrontendSearch = $this->createMock(FrontendSearch::class);
        $FrontendSearch->method('getSearchFieldData')->willReturn([]);
        $Control = new Search([
            'Site' => ProductTestHelper::getCategorySite(),
            'freeTextSearch' => false
        ]);
        (new ReflectionProperty(Search::class, 'Search'))->setValue($Control, $FrontendSearch);

        self::assertFalse($Control->hasFields());
        $html = $Control->create();

        self::assertStringContainsString('<form>', $html);
        self::assertStringNotContainsString('quiqqer-products-search-freetext', $html);
        self::assertStringNotContainsString('quiqqer-products-search-fieldplaceholder-select', $html);
    }
}
