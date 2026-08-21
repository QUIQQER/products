<?php

namespace QUITests\ERP\Products\Unit\Controls;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Controls\Category\Menu;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Rewrite;
use ReflectionMethod;

class CategoryMenuTest extends TestCase
{
    private ?Rewrite $originalRewrite;

    protected function setUp(): void
    {
        $this->originalRewrite = QUI::$Rewrite;
    }

    protected function tearDown(): void
    {
        QUI::$Rewrite = $this->originalRewrite;
    }

    public function testBodyRendersCategoryNavigationAndReusesItsCacheEntry(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn('category_menu_project');
        $Project->method('getLang')->willReturn('de');

        $Child = $this->createSite(12, 8, 'Child category', 42, $Project);
        $Child->method('getNavigation')->willReturn([]);
        $Child->method('getUrlRewritten')->willReturn('/child-category');
        $Parent = $this->createSite(8, 1, 'Parent category', 41, $Project);
        $Parent->method('getNavigation')->willReturn([$Child]);

        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getSite')->willReturn($Parent);
        $Rewrite->method('isIdInPath')->willReturn(true);
        QUI::$Rewrite = $Rewrite;

        $Menu = new Menu([
            'Site' => $Parent,
            'breadcrumb' => true,
            'showTitle' => true,
            'frontendTitle' => 'Categories'
        ]);
        $CacheMethod = new ReflectionMethod(Menu::class, 'getCacheName');
        $cacheName = $CacheMethod->invoke($Menu);
        QUI\Cache\LongTermCache::clear($cacheName);

        try {
            $firstHtml = $Menu->getBody();
            $secondHtml = $Menu->getBody();

            self::assertSame($firstHtml, $secondHtml);
            self::assertStringContainsString('<h1>Categories</h1>', $firstHtml);
            self::assertStringContainsString('Child category', $firstHtml);
            self::assertStringContainsString('fa-angle-right', $firstHtml);
            self::assertStringContainsString(
                'quiqqer-products-category-menu-navigation__isInBreadcrumb',
                $firstHtml
            );
        } finally {
            QUI\Cache\LongTermCache::clear($cacheName);
        }
    }

    public function testCheckboxRulesFollowCurrentSiteParentAndFilterSettings(): void
    {
        $Project = $this->createMock(Project::class);
        $MenuSite = $this->createSite(8, 1, 'Menu', 41, $Project, true);
        $Child = $this->createSite(12, 8, 'Child', 42, $Project);
        $Unrelated = $this->createSite(13, 99, 'Unrelated', 43, $Project);
        $Current = $this->createSite(8, 1, 'Current', 41, $Project, true);

        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getSite')->willReturn($Current);
        $Rewrite->method('isIdInPath')->willReturnCallback(static fn (int $id): bool => $id === 8);
        QUI::$Rewrite = $Rewrite;

        $Menu = new Menu(['Site' => $MenuSite]);
        self::assertTrue($Menu->hasCategoryCheckBox($Child));
        self::assertFalse($Menu->hasCategoryCheckBox($Current));
        self::assertFalse($Menu->hasCategoryCheckBox($Unrelated));

        $DisabledMenu = new Menu([
            'Site' => $MenuSite,
            'disableCheckboxes' => true
        ]);
        self::assertFalse($DisabledMenu->hasCategoryCheckBox($Child));

        $Root = $this->createSite(1, 0, 'Root', null, $Project);
        $RootRewrite = $this->createMock(Rewrite::class);
        $RootRewrite->method('getSite')->willReturn($Root);
        QUI::$Rewrite = $RootRewrite;
        self::assertTrue($Menu->hasCategoryCheckBox($Child));
    }

    public function testChildrenCountsAndBreadcrumbFlagsExposeNavigationState(): void
    {
        $Project = $this->createMock(Project::class);
        $First = $this->createSite(12, 8, 'First', 42, $Project);
        $Second = $this->createSite(13, 8, 'Second', 43, $Project);
        $Parent = $this->createSite(8, 1, 'Parent', 41, $Project);
        $Parent->method('getNavigation')->willReturnCallback(
            static fn (array $params): array|int => !empty($params['count']) ? 2 : [$First, $Second]
        );

        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getSite')->willReturn($Parent);
        $Rewrite->method('isIdInPath')->willReturnCallback(static fn (int $id): bool => $id === 12);
        QUI::$Rewrite = $Rewrite;

        $Menu = new Menu(['Site' => $Parent, 'breadcrumb' => true]);
        self::assertSame([$First, $Second], $Menu->getChildren());
        self::assertSame(2, $Menu->countChildren());
        self::assertTrue($Menu->useBreadcrumbFlag($First));
        self::assertFalse($Menu->useBreadcrumbFlag($Second));
        self::assertSame(
            'quiqqer-products-category-menu-navigation__isInBreadcrumb',
            $Menu->getBreadcrumbFlag($First)
        );
        self::assertSame('', $Menu->getBreadcrumbFlag($Second));

        $MenuWithoutBreadcrumb = new Menu(['Site' => $Parent]);
        self::assertFalse($MenuWithoutBreadcrumb->useBreadcrumbFlag($First));
    }

    public function testMissingConfiguredAndRewriteSiteIsReported(): void
    {
        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getSite')->willReturn(null);
        QUI::$Rewrite = $Rewrite;
        $Menu = new Menu();

        self::assertSame(0, $Menu->countChildren());

        $Method = new ReflectionMethod(Menu::class, 'getSite');
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionMessage('Could not determine the category menu site.');
        $Method->invoke($Menu);
    }

    /**
     * @return Site&MockObject
     */
    private function createSite(
        int $id,
        int $parentId,
        string $title,
        ?int $categoryId,
        Project $Project,
        bool $categoryAsFilter = false
    ): Site&MockObject {
        $Site = $this->createMock(Site::class);
        $Site->method('getId')->willReturn($id);
        $Site->method('getParentId')->willReturn($parentId);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getAttribute')->willReturnCallback(
            static fn (string $key): mixed => match ($key) {
                'title' => $title,
                'quiqqer.products.settings.categoryId' => $categoryId,
                'quiqqer.products.settings.categoryAsFilter' => $categoryAsFilter,
                default => false
            }
        );

        return $Site;
    }
}
