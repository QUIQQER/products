<?php

namespace QUITests\ERP\Products\Integration\Controls\Search;

use QUI;
use QUI\ERP\Products\Controls\Search\Suggest;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class SuggestTest extends ProductIntegrationTestCase
{
    public function testCategorySiteRendersSearchFormWithExplicitGlobalOptions(): void
    {
        $Site = ProductTestHelper::getCategorySite();
        $Control = new Suggest([
            'Site' => $Site,
            'Project' => ProductTestHelper::getProject(),
            'globalsearch' => true,
            'limit' => 7,
            'showLinkToSearchSite' => true,
            'showPrice' => true,
            'showDesc' => false
        ]);

        $html = $Control->create();

        self::assertStringContainsString('quiqqer-products-search-suggest-form', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('data-name="suggestForm"', $html);
        self::assertStringContainsString('data-name="suggestInput"', $html);
        self::assertStringContainsString('data-name="suggestButton"', $html);
        self::assertStringContainsString('action="' . $Site->getUrlRewritten() . '"', $html);
        self::assertSame(1, $Control->getAttribute('data-qui-options-globalsearch'));
        self::assertSame(7, $Control->getAttribute('data-qui-options-limit'));
        self::assertTrue($Control->getAttribute('data-qui-options-showlinktosearchsite'));
        self::assertSame(1, $Control->getAttribute('data-qui-options-showprice'));
        self::assertSame(0, $Control->getAttribute('data-qui-options-showdesc'));
        self::assertSame(
            $Site->getUrlRewritten(),
            $Control->getAttribute('data-qui-options-searchurl')
        );
    }

    public function testProjectFallbackUsesFirstSiteWhenNoSearchSiteIsConfigured(): void
    {
        $Project = ProductTestHelper::getProject();
        $FirstSite = $Project->firstChild();
        $Control = new Suggest([
            'Project' => $Project,
            'limit' => 3,
            'showLinkToSearchSite' => false
        ]);

        $html = $Control->create();

        self::assertStringContainsString('quiqqer-products-search-suggest-form', $html);
        self::assertStringContainsString('action="' . $FirstSite->getUrlRewritten() . '"', $html);
        self::assertSame(3, $Control->getAttribute('data-qui-options-limit'));
        self::assertSame(
            $FirstSite->getUrlRewritten(),
            $Control->getAttribute('data-qui-options-searchurl')
        );
    }

    public function testConfiguredFreeTextSearchSuppressesSuggestForm(): void
    {
        $Site = ProductTestHelper::getCategorySite();
        $Site->setAttribute('quiqqer.products.settings.showFreeText', true);
        $Control = new Suggest([
            'Site' => $Site,
            'Project' => ProductTestHelper::getProject()
        ]);

        self::assertSame('', $Control->getBody());
    }

    public function testPriceOptionsUseConfigurationAndAllowExplicitDisabling(): void
    {
        $Config = QUI::getPackage('quiqqer/productsearch')->getConfig();
        self::assertNotNull($Config);
        $originalSettings = $Config->getSection('frontendSuggestSearch');
        $Config->set('frontendSuggestSearch', 'showPrice', 1);
        $Config->set('frontendSuggestSearch', 'showDesc', 0);

        try {
            $attributes = [
                'Site' => ProductTestHelper::getCategorySite(),
                'Project' => ProductTestHelper::getProject()
            ];
            $Configured = new Suggest($attributes);
            $Configured->create();
            self::assertSame(1, $Configured->getAttribute('data-qui-options-showprice'));
            self::assertSame(0, $Configured->getAttribute('data-qui-options-showdesc'));

            $Disabled = new Suggest(array_merge($attributes, ['showPrice' => false, 'showDesc' => true]));
            $Disabled->create();
            self::assertSame(0, $Disabled->getAttribute('data-qui-options-showprice'));
            self::assertSame(1, $Disabled->getAttribute('data-qui-options-showdesc'));
        } finally {
            $Config->setSection('frontendSuggestSearch', $originalSettings);
        }
    }
}
