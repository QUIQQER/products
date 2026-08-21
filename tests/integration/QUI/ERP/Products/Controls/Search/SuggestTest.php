<?php

namespace QUITests\ERP\Products\Integration\Controls\Search;

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
            'showLinkToSearchSite' => true
        ]);

        $html = $Control->create();

        self::assertStringContainsString('quiqqer-products-search-suggest-form', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('action="' . $Site->getUrlRewritten() . '"', $html);
        self::assertSame(1, $Control->getAttribute('data-qui-options-globalsearch'));
        self::assertSame(7, $Control->getAttribute('data-qui-options-limit'));
        self::assertTrue($Control->getAttribute('data-qui-options-showlinktosearchsite'));
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
}
