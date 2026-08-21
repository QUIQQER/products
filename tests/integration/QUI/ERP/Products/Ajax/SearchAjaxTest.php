<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Utils\Tables;
use QUI\Interfaces\Template\EngineInterface;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class SearchAjaxTest extends AjaxTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testBackendGridSearchReturnsOrderedProductRowsAndCount(): void
    {
        $First = ProductTestHelper::createProduct('ajax-search-alpha', 11.5);
        $Second = ProductTestHelper::createProduct('ajax-search-beta', 22.75);
        foreach ([$First, $Second] as $Product) {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                $Product->activate($SystemUser);
            });
        }

        $cacheRows = QUI::getDataBaseConnection()->fetchAllAssociative(
            'SELECT id, active, lang, type FROM '
            . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductCacheTableName())
            . ' WHERE id IN (?, ?) AND lang = ?',
            [$First->getId(), $Second->getId(), QUI\ERP\Products\Handler\Products::getLocale()->getCurrent()]
        );
        self::assertCount(2, $cacheRows);
        self::assertSame([1, 1], array_map('intval', array_column($cacheRows, 'active')));

        $searchParams = json_encode([
            'fields' => [],
            'active' => true,
            'ignoreFindVariantParentsByChildValues' => true,
            'sortOn' => 'id',
            'sortBy' => 'DESC',
            'limit' => 20,
            'sheet' => 1
        ], JSON_THROW_ON_ERROR);

        self::assertSame(
            [$Second->getId(), $First->getId()],
            array_map(
                'intval',
                SearchHandler::getBackendSearch()->search(json_decode($searchParams, true, flags: JSON_THROW_ON_ERROR))
            )
        );

        $originalDirectory = getcwd();
        chdir(dirname(__DIR__, 6) . '/ajax/search/backend');

        try {
            require 'execute.php';
            $result = $this->invokeEndpointAsAdmin(
                'search/backend/executeForGrid.php',
                'package_quiqqer_products_ajax_search_backend_executeForGrid',
                $searchParams
            );
        } finally {
            chdir($originalDirectory);
        }

        self::assertSame(1, $result['page']);
        self::assertGreaterThanOrEqual(2, $result['total']);
        self::assertSame(
            [$Second->getId(), $First->getId()],
            array_map('intval', array_column($result['data'], 'id'))
        );

        $rowsById = array_column($result['data'], null, 'id');
        self::assertSame('ajax-search-alpha', $rowsById[$First->getId()]['title']);
        self::assertSame(11.5, $rowsById[$First->getId()]['price_netto']);
        self::assertSame('EUR', $rowsById[$First->getId()]['price_currency']);
        self::assertSame('ajax-search-beta', $rowsById[$Second->getId()]['title']);
        self::assertSame(22.75, $rowsById[$Second->getId()]['price_netto']);
    }

    public function testBackendGridSearchReturnsStableEmptyResult(): void
    {
        $originalDirectory = getcwd();
        chdir(dirname(__DIR__, 6) . '/ajax/search/backend');

        try {
            require 'execute.php';
            $result = $this->invokeEndpointAsAdmin(
                'search/backend/executeForGrid.php',
                'package_quiqqer_products_ajax_search_backend_executeForGrid',
                json_encode([
                    'freetext' => 'definitely-no-phpunit-product',
                    'ignoreFindVariantParentsByChildValues' => true
                ], JSON_THROW_ON_ERROR)
            );
        } finally {
            chdir($originalDirectory);
        }

        self::assertSame([
            'data' => [],
            'total' => 0,
            'page' => 1
        ], $result);
    }

    public function testFrontendSuggestionsReturnActiveCategoryProducts(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-suggest-product', 18.75);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Project = ProductTestHelper::getProject();
        $projectData = json_encode([
            'name' => $Project->getName(),
            'lang' => $Project->getLang()
        ], JSON_THROW_ON_ERROR);
        $searchParams = json_encode(['fields' => []], JSON_THROW_ON_ERROR);

        $suggestions = $this->invokeEndpointAsAdmin(
            'search/frontend/suggest.php',
            'package_quiqqer_products_ajax_search_frontend_suggest',
            $projectData,
            ProductTestHelper::getCategorySite()->getId(),
            $searchParams
        );

        self::assertSame([$Product->getId()], array_map('intval', $suggestions));
    }

    public function testRenderedFrontendSuggestionsExposeSearchResultsAndPaginationToTemplate(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-rendered-suggestion', 31.25);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Project = ProductTestHelper::getProject();
        $assigned = [];
        $Engine = $this->createMock(EngineInterface::class);
        $Engine->expects(self::once())->method('assign')->willReturnCallback(
            static function (array $variables) use (&$assigned): void {
                $assigned = $variables;
            }
        );
        $Engine->expects(self::once())->method('fetch')->with(
            dirname(__DIR__, 6) . '/template/search/frontend/SuggestRendered.html'
        )->willReturn('<suggestions/>');
        $Template = $this->createMock(QUI\Template::class);
        $Template->method('getEngine')->willReturn($Engine);
        $originalTemplate = QUI::$Template;
        QUI::$Template = $Template;

        try {
            $html = $this->invokeEndpointAsAdmin(
                'search/frontend/suggestRendered.php',
                'package_quiqqer_products_ajax_search_frontend_suggestRendered',
                json_encode([
                    'name' => $Project->getName(),
                    'lang' => $Project->getLang()
                ], JSON_THROW_ON_ERROR),
                ProductTestHelper::getCategorySite()->getId(),
                json_encode(['page' => 0], JSON_THROW_ON_ERROR),
                null,
                0,
                true,
                '/products/search'
            );
        } finally {
            QUI::$Template = $originalTemplate;
        }

        self::assertSame('<suggestions/>', $html);
        self::assertContains($Product->getId(), array_map('intval', $assigned['result']));
        self::assertSame(1, $assigned['active']);
        self::assertGreaterThanOrEqual(1.0, $assigned['pages']);
        self::assertTrue($assigned['showLinkToSearchSite']);
        self::assertSame('/products/search', $assigned['searchUrl']);
        self::assertSame(QUI::getUserBySession()->getLocale(), $assigned['Locale']);
    }

    public function testRenderedFrontendSuggestionsReturnEmptyMarkupWithoutResults(): void
    {
        $Project = ProductTestHelper::getProject();

        self::assertSame('', $this->invokeEndpointAsAdmin(
            'search/frontend/suggestRendered.php',
            'package_quiqqer_products_ajax_search_frontend_suggestRendered',
            json_encode([
                'name' => $Project->getName(),
                'lang' => $Project->getLang()
            ], JSON_THROW_ON_ERROR),
            ProductTestHelper::getCategorySite()->getId(),
            json_encode([
                'freetext' => 'definitely-no-rendered-phpunit-product',
                'fields' => []
            ], JSON_THROW_ON_ERROR),
            false,
            3,
            false,
            ''
        ));
    }

    public function testFrontendSearchMetadataEndpointsResolveConfiguredSite(): void
    {
        $Project = ProductTestHelper::getProject();
        $projectData = json_encode([
            'name' => $Project->getName(),
            'lang' => $Project->getLang()
        ], JSON_THROW_ON_ERROR);
        $siteId = ProductTestHelper::getCategorySite()->getId();

        $fields = $this->invokeEndpoint(
            'search/frontend/getSearchFields.php',
            'package_quiqqer_products_ajax_search_frontend_getSearchFields',
            $siteId,
            $projectData,
            ''
        );
        $filteredFields = $this->invokeEndpoint(
            'search/frontend/getSearchFields.php',
            'package_quiqqer_products_ajax_search_frontend_getSearchFields',
            $siteId,
            $projectData,
            json_encode(['limit' => 2], JSON_THROW_ON_ERROR)
        );
        $fieldData = $this->invokeEndpoint(
            'search/frontend/getSearchFieldData.php',
            'package_quiqqer_products_ajax_search_frontend_getSearchFieldData',
            $siteId,
            $projectData
        );

        self::assertIsArray($fields);
        self::assertIsArray($filteredFields);
        self::assertLessThanOrEqual(count($fields), count($filteredFields));
        self::assertIsArray($fieldData);
    }

    public function testSearchCacheEndpointInvalidatesStoredSearchResult(): void
    {
        $cacheKey = 'phpunit-products-ajax-search-cache';
        QUI\ERP\Products\Search\Cache::set($cacheKey, ['ids' => [1, 2]]);

        self::assertNull($this->invokeEndpointAsAdmin(
            'search/clearSearchCache.php',
            'package_quiqqer_products_ajax_search_clearSearchCache'
        ));

        $this->expectException(QUI\Cache\Exception::class);
        QUI\ERP\Products\Search\Cache::get($cacheKey);
    }

    public function testSearchConfigurationEndpointsPersistSelectionsAndFrontendExecution(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-configured-search', 34.5);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
            $Product->activate($SystemUser);
        });
        $Project = ProductTestHelper::getProject();
        $Site = ProductTestHelper::getCategorySite();
        $projectData = json_encode([
            'name' => $Project->getName(),
            'lang' => $Project->getLang()
        ], JSON_THROW_ON_ERROR);
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $configKeys = ['backend', 'productSearchFields', 'frontend', 'freetext'];
        $originalConfig = [];
        foreach ($configKeys as $key) {
            $originalConfig[$key] = $Config->get('search', $key);
        }
        $searchFieldSetting = 'quiqqer.products.settings.searchFieldIds';
        $originalSiteFields = $Site->getAttribute($searchFieldSetting);

        try {
            $backendFields = $this->invokeEndpointAsAdmin(
                'search/backend/setSearchFields.php',
                'package_quiqqer_products_ajax_search_backend_setSearchFields',
                json_encode([
                    Fields::FIELD_TITLE => true,
                    Fields::FIELD_PRICE => false
                ], JSON_THROW_ON_ERROR)
            );
            self::assertTrue($backendFields[Fields::FIELD_TITLE]);
            self::assertFalse($backendFields[Fields::FIELD_PRICE]);

            $productFields = $this->invokeEndpointAsAdmin(
                'search/backend/setProductSearchFields.php',
                'package_quiqqer_products_ajax_search_backend_setProductSearchFields',
                json_encode([
                    Fields::FIELD_TITLE => false,
                    Fields::FIELD_PRICE => true
                ], JSON_THROW_ON_ERROR)
            );
            self::assertFalse($productFields[Fields::FIELD_TITLE]);
            self::assertTrue($productFields[Fields::FIELD_PRICE]);

            $productFieldData = $this->invokeEndpointAsAdmin(
                'search/backend/getProductSearchFieldsData.php',
                'package_quiqqer_products_ajax_search_backend_getProductSearchFieldsData'
            );
            self::assertSame(
                [Fields::FIELD_PRICE],
                array_map('intval', array_column($productFieldData, 'id'))
            );
            self::assertNotSame('', $productFieldData[0]['title']);

            $frontendFields = $this->invokeEndpointAsAdmin(
                'search/frontend/setSearchFields.php',
                'package_quiqqer_products_ajax_search_frontend_setSearchFields',
                json_encode([
                    Fields::FIELD_TITLE => false,
                    Fields::FIELD_PRICE => true
                ], JSON_THROW_ON_ERROR),
                $Site->getId(),
                $projectData
            );
            self::assertFalse($frontendFields[Fields::FIELD_TITLE]);
            self::assertTrue($frontendFields[Fields::FIELD_PRICE]);

            $globalFrontendFields = $this->invokeEndpointAsAdmin(
                'search/frontend/setGlobalSearchFields.php',
                'package_quiqqer_products_ajax_search_frontend_setGlobalSearchFields',
                json_encode([Fields::FIELD_TITLE => true], JSON_THROW_ON_ERROR)
            );
            self::assertTrue($globalFrontendFields[Fields::FIELD_TITLE]);

            $globalFields = $this->invokeEndpointAsAdmin(
                'search/global/setSearchFields.php',
                'package_quiqqer_products_ajax_search_global_setSearchFields',
                json_encode([Fields::FIELD_TITLE => true], JSON_THROW_ON_ERROR)
            );
            self::assertTrue($globalFields[Fields::FIELD_TITLE]);

            $searchResult = $this->invokeEndpointAsAdmin(
                'search/frontend/execute.php',
                'package_quiqqer_products_ajax_search_frontend_execute',
                $projectData,
                $Site->getId(),
                json_encode([
                    'fields' => [],
                    'sortOn' => 'Sid',
                    'sortBy' => 'ASC'
                ], JSON_THROW_ON_ERROR)
            );
            self::assertContains($Product->getId(), array_map('intval', $searchResult));

            $count = $this->invokeEndpointAsAdmin(
                'search/frontend/execute.php',
                'package_quiqqer_products_ajax_search_frontend_execute',
                $projectData,
                $Site->getId(),
                json_encode(['fields' => [], 'count' => true], JSON_THROW_ON_ERROR)
            );
            self::assertGreaterThanOrEqual(1, $count);
        } finally {
            foreach ($originalConfig as $key => $value) {
                if ($value === false) {
                    $Config->del('search', $key);
                } else {
                    $Config->set('search', $key, $value);
                }
            }
            $Config->save();
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use (
                $Site,
                $searchFieldSetting,
                $originalSiteFields
            ): void {
                $Site->setAttribute($searchFieldSetting, $originalSiteFields);
                $Site->save($SystemUser);
            });
            QUI\ERP\Products\Search\Cache::clear();
        }
    }
}
