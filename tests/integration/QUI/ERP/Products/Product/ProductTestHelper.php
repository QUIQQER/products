<?php

namespace QUITests\ERP\Products\Integration\Product;

use Doctrine\DBAL\Connection;
use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\EventHandling;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Field\Types\Folder;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\Types\InputMultiLang;
use QUI\ERP\Products\Field\Types\IntType;
use QUI\ERP\Products\Field\Types\Price;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\AbstractType;
use QUI\ERP\Products\Utils\Tables;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Projects\Project;
use QUI\Projects\Site\Edit;
use QUITests\ERP\Products\Integration\IntegrationTestEnvironment;
use QUITests\ERP\Products\Integration\ProjectTestHelper;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class ProductTestHelper
{
    public const PREFIX = 'phpunit-products-dbal-';

    /** @var array<int, true> */
    private static array $createdProductIds = [];
    private static ?int $categoryId = null;
    private static ?int $siteId = null;
    private static bool $cleanupRegistered = false;
    private static mixed $originalProductsFolder = null;
    private static bool $productsFolderChanged = false;

    /** @var array<int, Field> */
    private static array $originalFieldList = [];
    /** @var array<string, mixed> */
    private static array $originalFieldCacheState = [];

    private static bool $fieldFixturesInstalled = false;

    public static function assertDatabaseIsAvailable(): void
    {
        try {
            $Connection = self::getConnection();
            $SchemaManager = QUI::getSchemaManager();
            $tables = [
                Tables::getProductTableName(),
                Tables::getProductCacheTableName(),
                Tables::getCategoryTableName()
            ];

            if (!$SchemaManager->tablesExist($tables)) {
                throw new RuntimeException('The Products database tables are not installed.');
            }

            $Connection->executeQuery(
                'SELECT 1 FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName()) . ' LIMIT 1'
            )->free();
        } catch (Throwable $Exception) {
            throw new RuntimeException(
                'QUIQQER Products database is not available: ' . $Exception->getMessage(),
                0,
                $Exception
            );
        }
    }

    public static function initialize(): void
    {
        if (self::$categoryId !== null) {
            return;
        }

        self::registerCleanup();
        IntegrationTestEnvironment::ensureDefaults();

        try {
            $Project = ProjectTestHelper::getProject();
            self::configureProductsFolder($Project);
            self::installFieldFixtures();
            $Category = self::runAsSystemUser(static function (): Category {
                $Category = Categories::createCategory(0, self::PREFIX . 'category');

                if (!$Category instanceof Category) {
                    throw new RuntimeException('The PHPUnit product category has an unexpected type.');
                }

                $Category->setCustomDataEntry('phpunitFixture', self::PREFIX);
                $Category->save();

                return $Category;
            });
            self::$categoryId = $Category->getId();
            self::$siteId = self::createCategorySite($Project, $Category);
            $Category = Categories::getCategory(self::$categoryId);

            if (!$Category instanceof Category) {
                throw new RuntimeException('The reloaded PHPUnit product category has an unexpected type.');
            }

            $Category->refreshSiteBinds();
            (new ReflectionProperty(Category::class, 'defaultSites'))->setValue($Category, [
                $Project->getName() => [
                    $Project->getLang() => new Edit($Project, self::$siteId)
                ]
            ]);
        } catch (Throwable $Exception) {
            self::cleanupAll();
            throw $Exception;
        }
    }

    public static function getProject(): Project
    {
        self::initialize();

        return ProjectTestHelper::getProject();
    }

    public static function getCategory(): Category
    {
        self::initialize();

        if (self::$categoryId === null) {
            throw new RuntimeException('The PHPUnit product category is unavailable.');
        }

        $Category = Categories::getCategory(self::$categoryId);

        if (!$Category instanceof Category) {
            throw new RuntimeException('The PHPUnit product category has an unexpected type.');
        }

        return $Category;
    }

    public static function getCategorySite(): Edit
    {
        self::initialize();

        if (self::$siteId === null) {
            throw new RuntimeException('The PHPUnit product category site is unavailable.');
        }

        return new Edit(ProjectTestHelper::getProject(), self::$siteId);
    }

    public static function createProduct(
        ?string $title = null,
        float $price = 42.5,
        string $productType = ''
    ): AbstractType {
        self::initialize();

        $suffix = bin2hex(random_bytes(8));
        $title ??= self::PREFIX . $suffix;
        $productNo = self::PREFIX . 'no-' . $suffix;
        $localizedTitle = [];
        $localizedUrl = [];

        foreach (self::getProject()->getLanguages() as $language) {
            $localizedTitle[$language] = $title;
            $localizedUrl[$language] = self::PREFIX . $suffix;
        }

        if ($localizedTitle === []) {
            $language = self::getProject()->getLang();
            $localizedTitle[$language] = $title;
            $localizedUrl[$language] = self::PREFIX . $suffix;
        }

        $Title = clone Fields::getField(Fields::FIELD_TITLE);
        $Title->setValue($localizedTitle);
        $Price = clone Fields::getField(Fields::FIELD_PRICE);
        $Price->setValue($price);
        $ProductNo = clone Fields::getField(Fields::FIELD_PRODUCT_NO);
        $ProductNo->setValue($productNo);
        $Url = clone Fields::getField(Fields::FIELD_URL);
        $Url->setValue($localizedUrl);

        try {
            $Product = self::runAsSystemUser(static fn (): AbstractType => Products::createProduct(
                [self::getCategory()],
                [$Title, $Price, $ProductNo, $Url],
                $productType
            ));
        } catch (Throwable $Exception) {
            self::cleanupProducts();
            throw $Exception;
        }

        self::$createdProductIds[$Product->getId()] = true;

        return $Product;
    }

    public static function installCompleteFieldFixtures(): void
    {
        self::initialize();
        (new ReflectionMethod(EventHandling::class, 'setDefaultProductFields'))->invoke(null);
    }

    public static function cleanupProducts(): void
    {
        try {
            $ids = array_unique(array_merge(array_keys(self::$createdProductIds), self::findFixtureProductIds()));

            foreach ($ids as $id) {
                try {
                    self::runAsSystemUser(static function () use ($id): void {
                        Products::cleanProductInstanceMemCache((int)$id);
                        Products::getNewProductInstance((int)$id)->delete();
                    });
                } catch (Throwable) {
                    // Direct table cleanup below is the final safety net.
                }
            }

            $Connection = self::getConnection();

            foreach ($ids as $id) {
                $Connection->delete(
                    QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductCacheTableName()),
                    ['id' => (int)$id]
                );
                $Connection->delete(
                    QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName()),
                    ['id' => (int)$id]
                );
                Products::cleanProductInstanceMemCache((int)$id);
            }
        } catch (Throwable) {
            // Cleanup must never hide the actual test result or shutdown reason.
        } finally {
            self::$createdProductIds = [];
        }
    }

    public static function cleanupCustomFields(): void
    {
        try {
            $Connection = self::getConnection();
            $fieldTable = QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName());
            $fieldIds = $Connection->fetchFirstColumn(
                'SELECT id FROM ' . $fieldTable . ' WHERE id >= 1000 ORDER BY id DESC'
            );

            foreach ($fieldIds as $fieldId) {
                $fieldId = (int)$fieldId;

                try {
                    self::runAsSystemUser(static function () use ($fieldId): void {
                        Fields::getField($fieldId)->delete();
                    });
                } catch (Throwable) {
                    $Connection->delete(Tables::getFieldTableName(), ['id' => $fieldId]);
                    Fields::removeRuntimeField($fieldId);
                }
            }

            $categoryTable = QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName());
            $categories = $Connection->fetchAllAssociative(
                'SELECT id, fields FROM ' . $categoryTable
            );

            foreach ($categories as $category) {
                $fields = json_decode((string)$category['fields'], true);

                if (!is_array($fields)) {
                    continue;
                }

                $filteredFields = array_values(array_filter(
                    $fields,
                    static fn (array $field): bool => (int)($field['id'] ?? 0) < 1000
                ));

                if ($filteredFields === $fields) {
                    continue;
                }

                $categoryId = (int)$category['id'];
                $Connection->update(
                    Tables::getCategoryTableName(),
                    ['fields' => json_encode($filteredFields, JSON_THROW_ON_ERROR)],
                    ['id' => $categoryId]
                );
                Categories::clearCache($categoryId);
            }
        } catch (Throwable) {
            // Test teardown must not hide the original test result.
        }
    }

    public static function cleanupAll(): void
    {
        self::cleanupProducts();
        self::cleanupCustomFields();
        $categoryIds = self::findFixtureCategoryIds();

        if (self::$categoryId !== null) {
            $categoryIds[] = self::$categoryId;
        }

        foreach (array_unique($categoryIds) as $categoryId) {
            try {
                self::runAsSystemUser(static function () use ($categoryId): void {
                    Categories::getCategory($categoryId)->delete(QUI::getUsers()->getSystemUser());
                });
            } catch (Throwable) {
                try {
                    self::getConnection()->delete(
                        QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName()),
                        ['id' => $categoryId]
                    );
                    Categories::clearCache($categoryId);
                } catch (Throwable) {
                    // Project and ERP cleanup below must still run.
                }
            }
        }

        self::$categoryId = null;
        self::$siteId = null;
        (new ReflectionProperty(Categories::class, 'list'))->setValue(null, []);
        Products::cleanProductInstanceMemCache();
        IntegrationTestEnvironment::cleanup();
        self::restoreProductsFolder();
        self::restoreFieldFixtures();
    }

    public static function runAsSystemUser(callable $Callback): mixed
    {
        return ProjectTestHelper::runAsSystemUser(
            static fn (): mixed => $Callback(QUI::getUsers()->getSystemUser())
        );
    }

    private static function createCategorySite(Project $Project, Category $Category): int
    {
        $Root = $Project->firstChild()->getEdit();
        $siteName = self::PREFIX . 'category-' . bin2hex(random_bytes(6));
        $siteId = self::runAsSystemUser(static fn (): int => $Root->createChild([
            'name' => $siteName,
            'title' => 'PHPUnit Products Category'
        ]));
        $Site = new Edit($Project, $siteId);

        self::runAsSystemUser(static function (UserInterface $SystemUser) use ($Site, $Category): void {
            $Site->setAttribute('type', 'quiqqer/products:types/category');
            $Site->setAttribute('quiqqer.products.settings.categoryId', $Category->getId());
            $Site->save($SystemUser);
        });

        self::getConnection()->update($Project->table(), ['active' => 1], ['id' => $siteId]);
        QUI\Cache\LongTermCache::clear(
            QUI\ERP\Products\Handler\Cache::getBasicCachePath()
            . 'category/' . $Category->getId() . '/sites'
        );
        QUI\Cache\LongTermCache::clear(
            'products/category/' . $Category->getId()
            . '/site/' . $Project->getName() . '/' . $Project->getLang()
        );

        return $siteId;
    }

    public static function registerCleanup(): void
    {
        if (self::$cleanupRegistered) {
            return;
        }

        self::$cleanupRegistered = true;
        QUI::getEvents()->addEvent(
            QUI\System\TestCleanup::EVENT,
            static function (): void {
                self::cleanupAll();
            }
        );
    }

    /**
     * @return int[]
     */
    private static function findFixtureProductIds(): array
    {
        $QueryBuilder = self::getConnection()->createQueryBuilder();
        $rows = $QueryBuilder
            ->select('id')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName()))
            ->where($QueryBuilder->expr()->like('fieldData', ':fixture'))
            ->setParameter('fixture', '%' . self::PREFIX . '%')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map('intval', $rows);
    }

    /**
     * @return int[]
     */
    private static function findFixtureCategoryIds(): array
    {
        try {
            $QueryBuilder = self::getConnection()->createQueryBuilder();
            $rows = $QueryBuilder
                ->select('id')
                ->from(QUI\Utils\Doctrine::quoteIdentifier(Tables::getCategoryTableName()))
                ->where($QueryBuilder->expr()->like('custom_data', ':fixture'))
                ->setParameter('fixture', '%' . self::PREFIX . '%')
                ->executeQuery()
                ->fetchFirstColumn();

            return array_map('intval', $rows);
        } catch (Throwable) {
            return [];
        }
    }

    private static function getConnection(): Connection
    {
        return QUI::getDataBaseConnection();
    }

    private static function configureProductsFolder(Project $Project): void
    {
        if (self::$productsFolderChanged) {
            return;
        }

        $Config = QUI::getPackage('quiqqer/products')->getConfig();

        if ($Config === null) {
            throw new RuntimeException('The Products package config is unavailable.');
        }

        self::$originalProductsFolder = $Config->get('products', 'folder');
        $Config->set('products', 'folder', $Project->getMedia()->firstChild()->getUrl());
        self::$productsFolderChanged = true;
    }

    private static function restoreProductsFolder(): void
    {
        if (!self::$productsFolderChanged) {
            return;
        }

        $Config = QUI::getPackage('quiqqer/products')->getConfig();

        if ($Config !== null) {
            if (self::$originalProductsFolder === false) {
                $Config->del('products', 'folder');
            } else {
                $Config->set('products', 'folder', self::$originalProductsFolder);
            }
        }

        self::$originalProductsFolder = null;
        self::$productsFolderChanged = false;
    }

    private static function installFieldFixtures(): void
    {
        if (self::$fieldFixturesInstalled) {
            return;
        }

        foreach (['fieldTypes', 'fieldTypeData', 'priceFactorSettings', 'deletedFieldIds'] as $property) {
            $Property = new ReflectionProperty(Fields::class, $property);
            self::$originalFieldCacheState[$property] = $Property->getValue();
        }

        $List = new ReflectionProperty(Fields::class, 'list');
        self::$originalFieldList = $List->getValue();
        $fieldDefinitions = [
            Fields::FIELD_PRICE => [Price::class, Fields::TYPE_PRICE, 1, 1],
            Fields::FIELD_PRODUCT_NO => [Input::class, Fields::TYPE_INPUT, 0, 1],
            Fields::FIELD_TITLE => [InputMultiLang::class, Fields::TYPE_INPUT_MULTI_LANG, 1, 1],
            Fields::FIELD_SHORT_DESC => [InputMultiLang::class, Fields::TYPE_INPUT_MULTI_LANG, 0, 1],
            Fields::FIELD_FOLDER => [Folder::class, Fields::TYPE_FOLDER, 0, 1],
            Fields::FIELD_PRIORITY => [IntType::class, Fields::TYPE_INT, 0, 0],
            Fields::FIELD_URL => [InputMultiLang::class, Fields::TYPE_INPUT_MULTI_LANG, 0, 0]
        ];
        $fieldList = [];
        $Connection = self::getConnection();
        $Connection->executeStatement(
            'DELETE FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName())
        );

        foreach ($fieldDefinitions as $id => [$className, $type, $required, $public]) {
            $data = [
                'id' => $id,
                'name' => 'phpunit-field-' . $id,
                'type' => $type,
                'search_type' => '',
                'prefix' => '',
                'suffix' => '',
                'priority' => $id,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => $required,
                'publicField' => $public,
                'showInDetails' => 1,
                'options' => null,
                'defaultValue' => null,
                'e_date' => '2026-01-01 00:00:00'
            ];
            $Connection->insert(Tables::getFieldTableName(), $data);

            /** @var Field $Field */
            $Field = new $className($id, [
                'system' => 1,
                'standard' => 1,
                'required' => $required,
                'public' => $public
            ]);
            $Field->setAttributes($data);
            $fieldList[$id] = $Field;
        }

        $List->setValue(null, $fieldList);

        foreach (array_keys($fieldList) as $fieldId) {
            if ($fieldList[$fieldId]->isSearchable()) {
                Fields::createFieldCacheColumn($fieldId);
            }
        }

        self::$fieldFixturesInstalled = true;
    }

    private static function restoreFieldFixtures(): void
    {
        if (!self::$fieldFixturesInstalled) {
            return;
        }

        (new ReflectionProperty(Fields::class, 'list'))->setValue(null, self::$originalFieldList);

        foreach (self::$originalFieldCacheState as $property => $value) {
            (new ReflectionProperty(Fields::class, $property))->setValue(null, $value);
        }

        self::$originalFieldList = [];
        self::$originalFieldCacheState = [];
        self::$fieldFixturesInstalled = false;
    }
}
