<?php

namespace QUITests\ERP\Products\Integration;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use QUI\ERP\Products\EventHandling;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Search;
use QUI\ERP\Products\Utils\Tables;
use QUI\Projects\Site;
use ReflectionMethod;
use ReflectionProperty;

class EventHandlingDatabaseTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $originalFieldState = [];

    /** @var array<string, mixed> */
    private array $originalProductsConfigState = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['list', 'fieldTypes', 'fieldTypeData', 'priceFactorSettings', 'deletedFieldIds'] as $property) {
            $Property = new ReflectionProperty(Fields::class, $property);
            $this->originalFieldState[$property] = $Property->getValue();
        }

        $Config = QUI::getPackage('quiqqer/products')->getConfig();

        if ($Config instanceof Config) {
            $this->originalProductsConfigState = (new ReflectionProperty(Config::class, 'iniParsedArray'))
                ->getValue($Config);
        }

        (new ReflectionProperty(Fields::class, 'list'))->setValue(null, []);
        QUI::getDataBaseConnection()->executeStatement(
            'DELETE FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName())
        );
    }

    protected function tearDown(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $groupsColumn = QUI\Utils\Doctrine::quoteIdentifier('groups');
        $varColumn = QUI\Utils\Doctrine::quoteIdentifier('var');
        $Connection->delete(Tables::getProductTableName(), ['id' => 8801]);
        $Connection->delete(Tables::getProductCacheTableName(), ['id' => 8801]);
        $Connection->delete(Tables::getCategoryTableName(), ['id' => 7701]);
        $Connection->delete(QUI\Translator::table(), [
            $groupsColumn => 'quiqqer/products',
            $varColumn => 'products.category.7701.title'
        ]);
        $Connection->delete(QUI\Translator::table(), [
            $groupsColumn => 'quiqqer/products',
            $varColumn => 'products.category.7701.description'
        ]);

        foreach ($this->originalFieldState as $property => $value) {
            (new ReflectionProperty(Fields::class, $property))->setValue(null, $value);
        }

        $Config = QUI::getPackage('quiqqer/products')->getConfig();

        if ($Config instanceof Config) {
            (new ReflectionProperty(Config::class, 'iniParsedArray'))->setValue(
                $Config,
                $this->originalProductsConfigState
            );
        }

        parent::tearDown();
    }

    public function testDefaultProductFieldSetupCreatesPortableIdempotentSchema(): void
    {
        $Setup = new ReflectionMethod(EventHandling::class, 'setDefaultProductFields');
        $Setup->invoke(null);

        $rows = QUI::getDataBaseConnection()->fetchAllAssociative(
            'SELECT id, type, requiredField, publicField FROM '
            . QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName())
            . ' ORDER BY id'
        );
        self::assertCount(24, $rows);
        $fields = [];

        foreach ($rows as $row) {
            $fields[(int)$row['id']] = $row;
        }

        self::assertSame('Price', $fields[Fields::FIELD_PRICE]['type']);
        self::assertSame(1, (int)$fields[Fields::FIELD_PRICE]['requiredField']);
        self::assertSame('InputMultiLang', $fields[Fields::FIELD_TITLE]['type']);
        self::assertSame(1, (int)$fields[Fields::FIELD_TITLE]['publicField']);
        self::assertSame('Folder', $fields[Fields::FIELD_FOLDER]['type']);
        self::assertSame(0, (int)$fields[Fields::FIELD_FOLDER]['requiredField']);
        self::assertSame('AttributeGroup', $fields[Fields::FIELD_CONDITION]['type']);

        $CacheTable = QUI::getSchemaManager()->introspectTable(Tables::getProductCacheTableName());
        self::assertTrue($CacheTable->hasColumn(Search::getSearchFieldColumnName(Fields::getField(Fields::FIELD_PRICE))));
        self::assertTrue($CacheTable->hasColumn(Search::getSearchFieldColumnName(Fields::getField(Fields::FIELD_TITLE))));
        self::assertFalse($CacheTable->hasColumn(Search::getSearchFieldColumnName(Fields::getField(Fields::FIELD_FOLDER))));

        $Setup->invoke(null);

        self::assertSame(
            24,
            (int)QUI::getDataBaseConnection()->fetchOne(
                'SELECT COUNT(*) FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName())
            )
        );
    }

    public function testDefaultVariantConfigurationRemovesMissingFieldsAndAddsDefaults(): void
    {
        (new ReflectionMethod(EventHandling::class, 'setDefaultProductFields'))->invoke(null);
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $Config->setSection('editableFields', [
            (string)Fields::FIELD_PRICE => 1,
            '999991' => 1
        ]);
        $Config->setSection('inheritedFields', [
            (string)Fields::FIELD_TITLE => 1,
            '999992' => 1
        ]);

        (new ReflectionMethod(EventHandling::class, 'setDefaultVariantFields'))->invoke(null);

        $editable = $Config->getSection('editableFields');
        $inherited = $Config->getSection('inheritedFields');
        self::assertArrayNotHasKey('999991', $editable);
        self::assertArrayNotHasKey('999992', $inherited);
        self::assertSame('1', (string)$editable[Fields::FIELD_PRICE]);
        self::assertSame('1', (string)$editable[Fields::FIELD_PRODUCT_NO]);
        self::assertSame('1', (string)$inherited[Fields::FIELD_VAT]);
        self::assertSame('1', (string)$inherited[Fields::FIELD_MANUFACTURER]);
    }

    public function testProductTypePatchUpdatesProductsAndLocalizedCacheRows(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $legacyType = '\\QUI\\ERP\\Products\\Product\\Types\\Product';
        $expectedType = 'QUI\\ERP\\Products\\Product\\Types\\Product';
        $Connection->insert(Tables::getProductTableName(), [
            'id' => 8801,
            'type' => $legacyType
        ]);
        foreach (['de', 'en'] as $language) {
            $Connection->insert(Tables::getProductCacheTableName(), [
                'id' => 8801,
                'type' => $legacyType,
                'lang' => $language,
                'title' => 'Legacy type'
            ]);
        }

        EventHandling::patchProductTypes();

        self::assertSame(
            $expectedType,
            $Connection->fetchOne('SELECT type FROM ' . Tables::getProductTableName() . ' WHERE id = 8801')
        );
        self::assertSame(
            [$expectedType, $expectedType],
            $Connection->fetchFirstColumn(
                'SELECT type FROM ' . Tables::getProductCacheTableName() . ' WHERE id = 8801 ORDER BY lang'
            )
        );
        EventHandling::patchProductTypes();
    }

    public function testCacheTableCheckRemovesColumnsForDeletedFields(): void
    {
        $columnName = 'F999993';
        QUI\ERP\Products\Utils\Database::addColumn(
            Tables::getProductCacheTableName(),
            $columnName,
            'VARCHAR(255)'
        );
        self::assertArrayHasKey(
            $columnName,
            QUI\ERP\Products\Utils\Database::getColumns(Tables::getProductCacheTableName())
        );

        EventHandling::checkProductCacheTable();

        self::assertArrayNotHasKey(
            $columnName,
            QUI\ERP\Products\Utils\Database::getColumns(Tables::getProductCacheTableName())
        );
    }

    public function testSiteLoadAndSaveHandlersApplyProductSiteRuntimeState(): void
    {
        $Project = $this->createMock(QUI\Projects\Project::class);
        $Project->method('getLang')->willReturn('de');
        $attributes = [
            'type' => 'quiqqer/products:types/category',
            'active' => false,
            'quiqqer.products.settings.categoryId' => 0
        ];
        $Site = $this->createMock(Site::class);
        $Site->method('getId')->willReturn(73);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getAttribute')->willReturnCallback(
            static fn (string $key): mixed => $attributes[$key] ?? false
        );
        $Site->expects(self::exactly(2))->method('setAttribute')->willReturnCallback(
            static function (string $key, mixed $value) use (&$attributes): void {
                $attributes[$key] = $value;
            }
        );

        EventHandling::onSiteLoad($Site);
        EventHandling::onSiteSave($Site);

        self::assertSame(1, $attributes['nocache']);
        self::assertTrue($attributes['quiqqer.products.settings.searchFieldIds.edited']);
    }

    public function testSiteSaveBeforeInstallsConfiguredDefaultSearchFieldsOnce(): void
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $originalDefaults = $Config->get('search', 'frontend');
        $Config->set('search', 'frontend', '1,4,7');
        $attributes = [
            'quiqqer.products.settings.searchFieldIds' => '',
            'quiqqer.products.settings.searchFieldIds.edited' => false
        ];
        $Site = $this->createMock(Site::class);
        $Site->method('getAttribute')->willReturnCallback(
            static fn (string $key): mixed => $attributes[$key] ?? false
        );
        $Site->expects(self::once())->method('setAttribute')->willReturnCallback(
            static function (string $key, mixed $value) use (&$attributes): void {
                $attributes[$key] = $value;
            }
        );

        try {
            EventHandling::onSiteSaveBefore($Site);
            self::assertSame(
                [1 => 1, 4 => 1, 7 => 1],
                json_decode($attributes['quiqqer.products.settings.searchFieldIds'], true)
            );
        } finally {
            $Config->set('search', 'frontend', $originalDefaults);
        }
    }

    public function testTemplateHeaderPublishesFrontendProductConfiguration(): void
    {
        $header = null;
        $Template = $this->createMock(QUI\Template::class);
        $Template->expects(self::once())->method('extendHeader')->willReturnCallback(
            static function (string $value) use (&$header): void {
                $header = $value;
            }
        );

        EventHandling::onTemplateGetHeader($Template);

        self::assertStringContainsString('QUIQQER_PRODUCTS_HIDE_PRICE', $header);
        self::assertStringContainsString('QUIQQER_PRODUCTS_FRONTEND_ANIMATION', $header);
        self::assertStringStartsWith('<script type="text/javascript">', $header);
    }

    public function testTemplateFooterLoadsProductDataLayerTracking(): void
    {
        $footer = null;
        $Collector = $this->createMock(QUI\Smarty\Collector::class);
        $Template = $this->createMock(QUI\Template::class);
        $Template->expects(self::once())->method('extendFooter')->willReturnCallback(
            static function (string $value) use (&$footer): void {
                $footer = $value;
            }
        );

        EventHandling::onTemplateEnd($Collector, $Template);

        self::assertStringContainsString('quiqqer/products/bin/dataLayerTracking.js', $footer);
        self::assertStringContainsString(' defer', $footer);
    }

    public function testSuccessfulOrderAddsArticleQuantitiesToPersistedOrderCount(): void
    {
        QUI::getDataBaseConnection()->insert(Tables::getProductTableName(), [
            'id' => 8801,
            'type' => 'QUI\\ERP\\Products\\Product\\Types\\Product',
            'orderCount' => 2
        ]);
        $Article = $this->createMock(QUI\ERP\Accounting\Article::class);
        $Article->method('getId')->willReturn(8801);
        $Article->method('getQuantity')->willReturn(3);
        $MissingArticle = $this->createMock(QUI\ERP\Accounting\Article::class);
        $MissingArticle->method('getId')->willReturn(999999);
        $MissingArticle->method('getQuantity')->willReturn(5);
        $Articles = $this->createMock(QUI\ERP\Accounting\ArticleList::class);
        $Articles->method('getIterator')->willReturn(new \ArrayIterator([$Article, $MissingArticle]));
        $Order = $this->createMock(QUI\ERP\Order\AbstractOrder::class);
        $Order->method('getArticles')->willReturn($Articles);

        EventHandling::onQuiqqerOrderSuccessful($Order);

        self::assertSame(
            5,
            (int)QUI::getDataBaseConnection()->fetchOne(
                'SELECT orderCount FROM ' . Tables::getProductTableName() . ' WHERE id = 8801'
            )
        );
    }

    public function testSmartyHandlerRegistersProductUtilities(): void
    {
        $Smarty = new \Smarty();
        EventHandling::onSmartyInit($Smarty);

        self::assertSame(
            '\QUI\ERP\Products\Utils\Products',
            $Smarty->registered_classes['QUI\ERP\Products\Utils\Products']
        );
        self::assertSame(
            '\QUI\ERP\Products\Handler\Fields',
            $Smarty->registered_classes['QUI\ERP\Products\Handler\Fields']
        );
        self::assertSame(
            '\QUI\ERP\Products\Handler\Products',
            $Smarty->registered_classes['QUI\ERP\Products\Handler\Products']
        );
    }

    public function testUnrelatedParentDoesNotTurnNewChildIntoProductCategory(): void
    {
        $Parent = $this->createMock(Site::class);
        $Parent->method('getAttribute')->with('type')->willReturn('quiqqer/sitetypes:types/text');
        $Parent->expects(self::never())->method('getProject');

        EventHandling::onSiteCreateChild(8802, $Parent);
    }

    public function testRequestHandlerStopsBeforeProductLookupForIncompleteProductUrls(): void
    {
        $originalGet = $_GET;
        $originalRequest = $_REQUEST;

        try {
            $_GET = [];
            $MissingUrl = $this->createMock(QUI\Rewrite::class);
            $MissingUrl->expects(self::never())->method('getUrlParamsList');
            EventHandling::onRequest($MissingUrl, 'ignored');

            $_GET['_url'] = '/regular/page/';
            $RegularUrl = $this->createMock(QUI\Rewrite::class);
            $RegularUrl->expects(self::never())->method('getUrlParamsList');
            EventHandling::onRequest($RegularUrl, 'ignored');

            $_GET['_url'] = '/_p/';
            $MissingParams = $this->createMock(QUI\Rewrite::class);
            $MissingParams->expects(self::once())->method('getUrlParamsList')->willReturn([]);
            EventHandling::onRequest($MissingParams, 'ignored');

            $MissingProductId = $this->createMock(QUI\Rewrite::class);
            $MissingProductId->expects(self::once())->method('getUrlParamsList')->willReturn(['_p']);
            EventHandling::onRequest($MissingProductId, 'ignored');
        } finally {
            $_GET = $originalGet;
            $_REQUEST = $originalRequest;
        }
    }

    public function testFrontendCacheClearInvalidatesPublishedProductCache(): void
    {
        QUI\Cache\LongTermCache::set('quiqqer/product/frontend', ['cached' => true]);

        EventHandling::onFrontendCacheClear();

        $this->expectException(QUI\Cache\Exception::class);
        QUI\Cache\LongTermCache::get('quiqqer/product/frontend');
    }

    public function testTranslatorEventsRefreshCategoryTitleAndDescriptionCaches(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $groupsColumn = QUI\Utils\Doctrine::quoteIdentifier('groups');
        $varColumn = QUI\Utils\Doctrine::quoteIdentifier('var');
        $Connection->insert(Tables::getCategoryTableName(), [
            'id' => 7701,
            'title_cache' => '',
            'description_cache' => ''
        ]);
        $Connection->insert(QUI\Translator::table(), [
            $groupsColumn => 'quiqqer/products',
            $varColumn => 'products.category.7701.title',
            'datatype' => 'php',
            'datadefine' => '',
            'html' => 0,
            'priority' => 0,
            'package' => 'quiqqer/products'
        ]);
        $Connection->insert(QUI\Translator::table(), [
            $groupsColumn => 'quiqqer/products',
            $varColumn => 'products.category.7701.description',
            'datatype' => 'php',
            'datadefine' => '',
            'html' => 0,
            'priority' => 0,
            'package' => 'quiqqer/products'
        ]);

        EventHandling::onQuiqqerTranslatorEdit(
            'quiqqer/products',
            'products.category.7701.title',
            'quiqqer/products',
            []
        );

        $cached = $Connection->fetchAssociative(
            'SELECT title_cache, description_cache FROM '
            . Tables::getCategoryTableName()
            . ' WHERE id = 7701'
        );
        self::assertIsArray($cached);
        self::assertSame(
            'products.category.7701.title',
            json_decode($cached['title_cache'], true, 512, JSON_THROW_ON_ERROR)['var']
        );
        self::assertSame(
            'products.category.7701.description',
            json_decode($cached['description_cache'], true, 512, JSON_THROW_ON_ERROR)['var']
        );

        EventHandling::onQuiqqerTranslatorEditById(1, [
            'groups' => 'quiqqer/products',
            'var' => 'products.category.7701.description',
            'package' => 'quiqqer/products'
        ]);
        EventHandling::onQuiqqerTranslatorEdit('vendor/other', 'products.category.7701.title', '', []);
        EventHandling::onQuiqqerTranslatorEdit('quiqqer/products', 'unrelated.variable', '', []);
        EventHandling::onQuiqqerTranslatorEdit('quiqqer/products', 'products.category.invalid.title', '', []);

        self::assertSame(
            'products.category.7701.description',
            json_decode(
                (string)$Connection->fetchOne(
                    'SELECT description_cache FROM ' . Tables::getCategoryTableName() . ' WHERE id = 7701'
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            )['var']
        );
    }
}
