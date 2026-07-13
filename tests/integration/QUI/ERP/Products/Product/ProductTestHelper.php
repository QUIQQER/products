<?php

namespace QUITests\ERP\Products\Integration\Product;

use Doctrine\DBAL\Connection;
use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\AbstractType;
use QUI\ERP\Products\Utils\Tables;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Projects\Project;
use QUI\Projects\ProjectTestHelper;
use QUI\Projects\Site\Edit;
use QUITests\ERP\Products\Integration\IntegrationTestEnvironment;
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
            $Category->refreshSiteBinds();
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

    public static function createProduct(?string $title = null, float $price = 42.5): AbstractType
    {
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
                [$Title, $Price, $ProductNo, $Url]
            ));
        } catch (Throwable $Exception) {
            self::cleanupProducts();
            throw $Exception;
        }

        self::$createdProductIds[$Product->getId()] = true;

        return $Product;
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

    public static function cleanupAll(): void
    {
        self::cleanupProducts();
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
        ProjectTestHelper::cleanup();
        IntegrationTestEnvironment::cleanup();
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
        $siteName = self::PREFIX . 'category';
        $siteId = self::runAsSystemUser(static fn (): int => $Root->createChild([
            'name' => $siteName,
            'title' => 'PHPUnit Products Category'
        ]));
        $Site = new Edit($Project, $siteId);

        self::runAsSystemUser(static function (UserInterface $SystemUser) use ($Site, $Category): void {
            $Site->setAttribute('type', 'quiqqer/products:types/category');
            $Site->setAttribute('quiqqer.products.settings.categoryId', $Category->getId());
            $Site->save($SystemUser);
            $Site->activate($SystemUser);
        });

        return $siteId;
    }

    private static function registerCleanup(): void
    {
        if (self::$cleanupRegistered) {
            return;
        }

        self::$cleanupRegistered = true;
        register_shutdown_function(static function (): void {
            self::cleanupAll();
        });
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
}
