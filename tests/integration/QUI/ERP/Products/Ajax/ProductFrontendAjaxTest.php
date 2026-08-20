<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Search\Cache as SearchCache;
use QUI\Rewrite;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class ProductFrontendAjaxTest extends AjaxTestCase
{
    private ?Rewrite $originalRewrite;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRewrite = QUI::$Rewrite;
        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getProject')->willReturn(ProductTestHelper::getProject());
        $Rewrite->method('getSite')->willReturn(ProductTestHelper::getCategorySite());
        $Rewrite->method('isIdInPath')->willReturn(false);
        QUI::$Rewrite = $Rewrite;
    }

    protected function tearDown(): void
    {
        QUI::$Rewrite = $this->originalRewrite;
        parent::tearDown();
    }

    public function testFiltersRenderFreeTextSearchAndConfiguredProductFields(): void
    {
        $Site = ProductTestHelper::getCategorySite();
        $freeTextSetting = 'quiqqer.products.settings.showFreeTextSearch';
        $fieldSetting = 'quiqqer.products.settings.searchFieldIds';
        $originalFreeTextSetting = $Site->getAttribute($freeTextSetting);
        $originalFieldSetting = $Site->getAttribute($fieldSetting);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use (
            $Site,
            $freeTextSetting,
            $fieldSetting
        ): void {
            $Site->setAttribute($freeTextSetting, true);
            $Site->setAttribute($fieldSetting, json_encode([
                Fields::FIELD_PRICE => true
            ], JSON_THROW_ON_ERROR));
            $Site->save($SystemUser);
        });
        $Project = ProductTestHelper::getProject();
        $cacheKey = $this->getSearchFieldDataCacheKey($Site->getId(), $Project->getLang());
        SearchCache::clear($cacheKey);

        try {
            $html = $this->invokeEndpoint(
                'products/frontend/getFilters.php',
                'package_quiqqer_products_ajax_products_frontend_getFilters',
                json_encode([
                    'name' => $Project->getName(),
                    'lang' => $Project->getLang()
                ], JSON_THROW_ON_ERROR),
                $Site->getId()
            );
        } finally {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use (
                $Site,
                $freeTextSetting,
                $fieldSetting,
                $originalFreeTextSetting,
                $originalFieldSetting
            ): void {
                $Site->setAttribute($freeTextSetting, $originalFreeTextSetting);
                $Site->setAttribute($fieldSetting, $originalFieldSetting);
                $Site->save($SystemUser);
            });
            SearchCache::clear($cacheKey);
        }

        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('name="search"', $html);
        self::assertStringContainsString('quiqqer-products-category-freetextSearch', $html);
        self::assertStringContainsString('quiqqer-products-productList-filter-container', $html);
        self::assertStringContainsString('data-fieldid="' . Fields::FIELD_PRICE . '"', $html);
    }

    public function testFiltersReturnEmptyMarkupForUnknownSite(): void
    {
        $Project = ProductTestHelper::getProject();

        self::assertSame('', $this->invokeEndpoint(
            'products/frontend/getFilters.php',
            'package_quiqqer_products_ajax_products_frontend_getFilters',
            json_encode([
                'name' => $Project->getName(),
                'lang' => $Project->getLang()
            ], JSON_THROW_ON_ERROR),
            999999
        ));
    }

    public function testVisitedProductsRenderOnlyActiveAlternatives(): void
    {
        $Current = ProductTestHelper::createProduct('ajax-visited-current', 10.0);
        $Visited = ProductTestHelper::createProduct('ajax-visited-active', 20.0);
        $Inactive = ProductTestHelper::createProduct('ajax-visited-inactive', 30.0);
        foreach ([$Current, $Visited, $Inactive] as $Product) {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                foreach (Fields::getStandardFields() as $Field) {
                    $Product->addField($Field);
                }

                $Product->save($SystemUser);

                if ($Product->getTitle() === 'ajax-visited-inactive') {
                    return;
                }

                $Product->activate($SystemUser);
            });
        }

        $html = $this->invokeEndpoint(
            'products/frontend/getVisitedProducts.php',
            'package_quiqqer_products_ajax_products_frontend_getVisitedProducts',
            json_encode([
                0,
                'invalid',
                $Current->getId(),
                $Visited->getId(),
                $Inactive->getId(),
                999999
            ], JSON_THROW_ON_ERROR),
            $Current->getId()
        );

        self::assertStringContainsString('data-pid="' . $Visited->getId() . '"', $html);
        self::assertStringContainsString('ajax-visited-active', $html);
        self::assertStringNotContainsString('data-pid="' . $Current->getId() . '"', $html);
        self::assertStringNotContainsString('data-pid="' . $Inactive->getId() . '"', $html);
    }

    public function testCustomFieldValuesAreNormalizedWithoutPersistingThePreview(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-custom-field-preview', 14.0);

        $result = $this->invokeEndpoint(
            'products/frontend/setCustomFieldValues.php',
            'package_quiqqer_products_ajax_products_frontend_setCustomFieldValues',
            $Product->getId(),
            json_encode([
                Fields::FIELD_PRICE => '19.75',
                999999 => 'ignored'
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame([Fields::FIELD_PRICE => 19.75], $result);
        Products::cleanProductInstanceMemCache($Product->getId());
        self::assertSame(
            14.0,
            Products::getNewProductInstance($Product->getId())->getFieldValue(Fields::FIELD_PRICE)
        );
    }

    private function getSearchFieldDataCacheKey(int $siteId, string $language): string
    {
        $groups = QUI::getUserBySession()->getGroups(false);
        sort($groups);

        return 'products/search/frontend/searchfielddata/'
            . $siteId . '/'
            . $language . '/'
            . md5(implode('', $groups));
    }
}
