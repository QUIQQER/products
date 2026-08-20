<?php

namespace QUITests\ERP\Products;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use QUI;
use QUI\Cache\Manager as CacheManager;
use QUI\Cache\LongTermCache;
use QUI\Config;
use QUI\Countries\Manager as CountriesManager;
use QUI\Database\DB;
use QUI\ERP\Currency\Handler as CurrencyHandler;
use QUI\ERP\Products\Handler\Categories as ProductCategories;
use QUI\ERP\Products\Handler\Fields as ProductFields;
use QUI\ERP\Products\Handler\Products as ProductHandler;
use QUI\ERP\Tax\Utils as TaxUtils;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Package\Package;
use QUI\Projects\Manager as ProjectManager;
use QUI\Projects\Project;
use QUI\Update;
use ReflectionProperty;
use Stash\Driver\Ephemeral;
use Stash\Pool;
use Throwable;

final class SqliteTestEnvironment
{
    private static ?Connection $connection = null;
    private static ?Connection $originalConnection = null;
    private static ?DB $originalLegacyDatabase = null;
    private static ?PermissionManager $originalPermissionManager = null;
    private static mixed $originalPermissionUser = null;
    private static mixed $originalSessionCountry = null;
    private static mixed $originalSessionUser = null;
    private static ?Config $originalProjectsConfig = null;
    private static ?Project $originalStandardProject = null;
    private static ?string $projectsConfigFile = null;
    private static ?Package $productsPackage = null;
    private static ?Config $originalProductsConfig = null;
    private static ?string $originalProductsConfigPath = null;
    private static ?string $productsConfigFile = null;

    /** @var array<string, array<string, Project>> */
    private static array $originalProjects = [];

    /** @var array<string, mixed> */
    private static array $originalCurrencyState = [];

    /** @var array<string, mixed> */
    private static array $originalCountriesState = [];

    /** @var array<string, mixed> */
    private static array $originalTaxState = [];

    /** @var array<string, mixed> */
    private static array $originalProductHandlerState = [];

    /** @var array<string, mixed> */
    private static array $originalCategoryHandlerState = [];

    /** @var array<string, mixed> */
    private static array $originalFieldHandlerState = [];

    /** @var array<string, mixed> */
    private static array $originalLongTermCacheState = [];

    /** @var array<string, mixed> */
    private static array $originalCacheManagerState = [];

    public static function activate(): void
    {
        if (self::$connection instanceof Connection) {
            return;
        }

        self::$originalConnection = QUI::getDataBaseConnection();
        self::$originalLegacyDatabase = QUI::$DataBase2;
        self::$originalPermissionManager = QUI::$Rights;
        self::$originalPermissionUser = (new ReflectionProperty(Permission::class, 'User'))->getValue();
        self::$originalCurrencyState = self::getStaticState(
            CurrencyHandler::class,
            ['currencies', 'Default', 'RuntimeCurrency']
        );
        self::$originalCountriesState = self::getStaticState(
            CountriesManager::class,
            ['countries', 'DefaultCountry']
        );
        self::$originalTaxState = self::getStaticState(TaxUtils::class, ['userTaxes']);
        self::$originalProductHandlerState = self::getStaticState(ProductHandler::class, ['list']);
        self::$originalCategoryHandlerState = self::getStaticState(ProductCategories::class, ['list']);
        self::$originalFieldHandlerState = self::getStaticState(ProductFields::class, [
            'list',
            'deletedFieldIds',
            'fieldTypes',
            'fieldTypeData',
            'priceFactorSettings'
        ]);
        self::$originalLongTermCacheState = self::getStaticState(LongTermCache::class, [
            'Config',
            'Pool',
            'Driver',
            'runtime'
        ]);
        self::$originalCacheManagerState = self::getStaticState(CacheManager::class, [
            'noClearing',
            'stashLoaded',
            'Stash',
            'FileSystemStash',
            'Handler',
            'handlers',
            'drivers',
            'currentDriver'
        ]);
        self::$originalSessionCountry = QUI::getSession()->get('country');
        self::$originalProjectsConfig = QUI::$Configs['etc/projects.ini'] ?? null;
        self::$originalProjects = ProjectManager::$projects;
        self::$originalStandardProject = ProjectManager::$Standard;

        $Users = QUI::getUsers();
        $Session = new ReflectionProperty($Users, 'Session');
        self::$originalSessionUser = $Session->getValue($Users);

        self::$connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);
        (new ReflectionProperty(Connection::class, 'platform'))->setValue(
            self::$connection,
            new SqlitePlatform()
        );

        try {
            self::setConnection(self::$connection);
            QUI::$DataBase2 = null;
            $LegacyDatabase = QUI::getDataBase();
            (new ReflectionProperty(DB::class, 'sqlite'))->setValue($LegacyDatabase, true);
            QUI::$Rights = null;
            $Session->setValue($Users, $Users->getSystemUser());
            Permission::setUser($Users->getSystemUser());
            self::setStaticState(CurrencyHandler::class, [
                'currencies' => [],
                'Default' => null,
                'RuntimeCurrency' => null
            ]);
            self::setStaticState(CountriesManager::class, [
                'countries' => [],
                'DefaultCountry' => null
            ]);
            self::setStaticState(TaxUtils::class, ['userTaxes' => []]);
            self::setStaticState(ProductHandler::class, ['list' => []]);
            self::setStaticState(ProductCategories::class, ['list' => []]);
            self::setStaticState(ProductFields::class, [
                'list' => [],
                'deletedFieldIds' => [],
                'fieldTypes' => null,
                'fieldTypeData' => [],
                'priceFactorSettings' => false
            ]);
            $LongTermCacheDriver = new Ephemeral();
            self::setStaticState(LongTermCache::class, [
                'Config' => null,
                'Pool' => new Pool($LongTermCacheDriver),
                'Driver' => $LongTermCacheDriver,
                'runtime' => []
            ]);
            $RuntimeCacheDriver = new Ephemeral();
            self::setStaticState(CacheManager::class, [
                'noClearing' => false,
                'stashLoaded' => true,
                'Stash' => new Pool($RuntimeCacheDriver),
                'FileSystemStash' => new Pool(new Ephemeral()),
                'Handler' => $RuntimeCacheDriver,
                'handlers' => [$RuntimeCacheDriver],
                'drivers' => [],
                'currentDriver' => null
            ]);
            QUI::getSession()->set('country', 'DE');
            self::activateProjectConfig();
            self::activateProductsConfig();

            Update::importDatabase(OPT_DIR . 'quiqqer/core/database.xml');
            Update::importDatabase(OPT_DIR . 'quiqqer/countries/database.xml');
            Update::importDatabase(OPT_DIR . 'quiqqer/currency/database.xml');
            Update::importDatabase(OPT_DIR . 'quiqqer/areas/database.xml');
            Update::importDatabase(OPT_DIR . 'quiqqer/tax/database.xml');
            Update::importDatabase(OPT_DIR . 'quiqqer/translator/database.xml');
            Update::importDatabase(dirname(__DIR__) . '/database.xml');

            self::$connection->insert(CurrencyHandler::table(), [
                'currency' => 'EUR',
                'rate' => 1,
                'autoupdate' => 0,
                'precision' => 2,
                'type' => CurrencyHandler::CURRENCY_TYPE_DEFAULT,
                'customData' => null
            ]);
            self::$connection->insert(QUI::getDBTableName('areas'), [
                'id' => 1,
                'countries' => 'DE',
                'data' => '{}'
            ]);

            PermissionManager::setup();
        } catch (Throwable $Exception) {
            self::restore();
            throw $Exception;
        }

        register_shutdown_function(static function (): void {
            self::restore();
        });
    }

    public static function restore(): void
    {
        if (!self::$connection instanceof Connection || !self::$originalConnection instanceof Connection) {
            return;
        }

        self::setConnection(self::$originalConnection);
        QUI::$DataBase2 = self::$originalLegacyDatabase;
        QUI::$Rights = self::$originalPermissionManager;
        (new ReflectionProperty(Permission::class, 'User'))->setValue(null, self::$originalPermissionUser);
        self::setStaticState(CurrencyHandler::class, self::$originalCurrencyState);
        self::setStaticState(CountriesManager::class, self::$originalCountriesState);
        self::setStaticState(TaxUtils::class, self::$originalTaxState);
        self::setStaticState(ProductHandler::class, self::$originalProductHandlerState);
        self::setStaticState(ProductCategories::class, self::$originalCategoryHandlerState);
        self::setStaticState(ProductFields::class, self::$originalFieldHandlerState);
        self::setStaticState(LongTermCache::class, self::$originalLongTermCacheState);
        self::setStaticState(CacheManager::class, self::$originalCacheManagerState);
        ProjectManager::$projects = self::$originalProjects;
        ProjectManager::$Standard = self::$originalStandardProject;

        if (self::$originalProjectsConfig instanceof Config) {
            QUI::$Configs['etc/projects.ini'] = self::$originalProjectsConfig;
        } else {
            unset(QUI::$Configs['etc/projects.ini']);
        }

        self::restoreProductsConfig();

        if (self::$originalSessionCountry === false) {
            QUI::getSession()->del('country');
        } else {
            QUI::getSession()->set('country', self::$originalSessionCountry);
        }

        (new ReflectionProperty(QUI::getUsers(), 'Session'))->setValue(
            QUI::getUsers(),
            self::$originalSessionUser
        );

        self::$connection->close();
        self::$connection = null;
        self::$originalConnection = null;
        self::$originalLegacyDatabase = null;

        if (self::$projectsConfigFile !== null && file_exists(self::$projectsConfigFile)) {
            unlink(self::$projectsConfigFile);
        }

        self::$projectsConfigFile = null;
    }

    private static function activateProjectConfig(): void
    {
        $projectsConfigFile = tempnam(sys_get_temp_dir(), 'phpunit-products-projects-');

        if ($projectsConfigFile === false) {
            throw new \RuntimeException('The isolated PHPUnit project config could not be created.');
        }

        self::$projectsConfigFile = $projectsConfigFile;
        QUI::$Configs['etc/projects.ini'] = new Config(self::$projectsConfigFile);
        ProjectManager::$projects = [];
        ProjectManager::$Standard = null;
    }

    private static function activateProductsConfig(): void
    {
        self::$productsPackage = QUI::getPackage('quiqqer/products');
        $ConfigProperty = new ReflectionProperty(self::$productsPackage, 'Config');
        $ConfigPathProperty = new ReflectionProperty(self::$productsPackage, 'configPath');
        self::$originalProductsConfig = $ConfigProperty->getValue(self::$productsPackage);
        self::$originalProductsConfigPath = $ConfigPathProperty->getValue(self::$productsPackage);
        $productsConfigFile = tempnam(sys_get_temp_dir(), 'phpunit-products-config-');

        if ($productsConfigFile === false) {
            throw new \RuntimeException('The isolated Products config could not be created.');
        }

        self::$productsConfigFile = $productsConfigFile;

        if (
            self::$originalProductsConfigPath !== null
            && file_exists(self::$originalProductsConfigPath)
        ) {
            copy(self::$originalProductsConfigPath, self::$productsConfigFile);
        }

        $ConfigPathProperty->setValue(self::$productsPackage, self::$productsConfigFile);
        $ConfigProperty->setValue(self::$productsPackage, new Config(self::$productsConfigFile));
    }

    private static function restoreProductsConfig(): void
    {
        if (!self::$productsPackage instanceof Package) {
            return;
        }

        (new ReflectionProperty(self::$productsPackage, 'Config'))->setValue(
            self::$productsPackage,
            self::$originalProductsConfig
        );
        (new ReflectionProperty(self::$productsPackage, 'configPath'))->setValue(
            self::$productsPackage,
            self::$originalProductsConfigPath
        );

        if (self::$productsConfigFile !== null && file_exists(self::$productsConfigFile)) {
            unlink(self::$productsConfigFile);
        }

        self::$productsPackage = null;
        self::$originalProductsConfig = null;
        self::$originalProductsConfigPath = null;
        self::$productsConfigFile = null;
    }

    private static function setConnection(Connection $Connection): void
    {
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $Connection);
    }

    /**
     * @param class-string $className
     * @param string[] $properties
     * @return array<string, mixed>
     */
    private static function getStaticState(string $className, array $properties): array
    {
        $state = [];

        foreach ($properties as $property) {
            $state[$property] = (new ReflectionProperty($className, $property))->getValue();
        }

        return $state;
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $state
     */
    private static function setStaticState(string $className, array $state): void
    {
        foreach ($state as $property => $value) {
            (new ReflectionProperty($className, $property))->setValue(null, $value);
        }
    }
}
