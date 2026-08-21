<?php

namespace QUITests\ERP\Products\Integration;

use Doctrine\DBAL\Schema\Table;
use QUI;
use QUI\Permissions\Permission;
use QUI\Projects\Manager;
use QUI\Projects\Media;
use QUI\Projects\Project;
use QUITests\ERP\Products\DatabaseEnvironment;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class ProjectTestHelper
{
    private const BASE_PROJECT_NAME = 'phpunit_products';
    private const LANGUAGE = 'de';

    private static ?string $projectName = null;

    public static function getProject(): Project
    {
        if (DatabaseEnvironment::usesCiDatabase()) {
            return Manager::getStandard();
        }

        if (self::$projectName === null) {
            self::createProject();
        }

        return QUI::getProject(self::$projectName, self::LANGUAGE);
    }

    public static function runAsSystemUser(callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $SystemUser = $Users->getSystemUser();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $PreviousSessionUser = $SessionProperty->getValue($Users);
        $PermissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $PreviousPermissionUser = $PermissionUserProperty->getValue();

        $SessionProperty->setValue($Users, $SystemUser);
        $PermissionUserProperty->setValue(null, $SystemUser);

        try {
            return $Callback();
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionUserProperty->setValue(null, $PreviousPermissionUser);
        }
    }

    private static function createProject(): void
    {
        self::assertDatabaseIsAvailable();
        $projectName = self::getAvailableProjectName();

        try {
            self::runAsSystemUser(static function () use ($projectName): void {
                self::createProjectTables($projectName);
                self::createProjectConfig($projectName);
            });
        } catch (Throwable $Exception) {
            try {
                QUI\System\TestCleanup::cleanupProject($projectName);
            } catch (Throwable) {
            }

            throw $Exception;
        }

        self::$projectName = $projectName;
    }

    private static function createProjectTables(string $projectName): void
    {
        $siteTable = QUI_DB_PRFX . $projectName . '_' . self::LANGUAGE . '_sites';
        $siteRelationsTable = $siteTable . '_relations';
        $mediaTable = QUI_DB_PRFX . $projectName . '_media';
        $mediaRelationsTable = $mediaTable . '_relations';
        $pathsTable = QUI_DB_PRFX . $projectName . '_' . self::LANGUAGE . '_paths';
        $multilingualTable = QUI_DB_PRFX . $projectName . '_multilingual';

        (new ReflectionMethod(Manager::class, 'createProjectSiteTables'))->invoke(
            null,
            $siteTable,
            $siteRelationsTable
        );
        (new ReflectionMethod(Project::class, 'ensureSitesTable'))->invoke(null, $siteTable);
        (new ReflectionMethod(Project::class, 'ensureSitesRelationTable'))->invoke(null, $siteRelationsTable);
        (new ReflectionMethod(Manager::class, 'createProjectMediaTables'))->invoke(
            null,
            $mediaTable,
            $mediaRelationsTable
        );
        (new ReflectionMethod(Media::class, 'ensureMediaTable'))->invoke(null, $mediaTable);
        (new ReflectionMethod(Media::class, 'ensureMediaRelationsTable'))->invoke(null, $mediaRelationsTable);

        $Paths = new Table($pathsTable);
        $Paths->addColumn('path', 'text', ['notnull' => false]);
        QUI::getSchemaManager()->createTable($Paths);

        $Multilingual = new Table($multilingualTable);
        $Multilingual->addColumn('id', 'bigint', ['autoincrement' => true]);
        $Multilingual->addColumn(self::LANGUAGE, 'bigint', ['notnull' => false]);
        $Multilingual->setPrimaryKey(['id']);
        QUI::getSchemaManager()->createTable($Multilingual);

        $date = date('Y-m-d H:i:s');
        $userUuid = QUI::getUserBySession()->getUUID();
        $Connection = QUI::getDataBaseConnection();
        $Connection->insert($siteTable, [
            'id' => 1,
            'name' => 'Start',
            'title' => 'start',
            'short' => 'Shorttext',
            'content' => '<p>PHPUnit Products</p>',
            'type' => 'standard',
            'active' => 1,
            'deleted' => 0,
            'c_date' => $date,
            'e_date' => $date,
            'c_user' => $userUuid,
            'e_user' => $userUuid,
            'nav_hide' => 0
        ]);
        $Connection->insert($mediaTable, [
            'id' => 1,
            'name' => 'Start',
            'title' => 'start',
            'short' => 'Shorttext',
            'type' => 'folder',
            'file' => '',
            'active' => 1,
            'deleted' => 0,
            'c_date' => $date,
            'e_date' => $date,
            'c_user' => $userUuid,
            'e_user' => $userUuid,
            'pathHash' => md5('')
        ]);
    }

    private static function createProjectConfig(string $projectName): void
    {
        $Config = Manager::getConfig();
        $Config->setSection($projectName, [
            'default_lang' => self::LANGUAGE,
            'langs' => self::LANGUAGE,
            'admin_mail' => '',
            'template' => '',
            'image_text' => '0',
            'keywords' => '',
            'description' => '',
            'robots' => 'noindex',
            'author' => '',
            'publisher' => '',
            'copyright' => '',
            'standard' => '0'
        ]);
        $Config->save();
        QUI\Utils\System\File::mkdir(CMS_DIR . 'media/sites/' . $projectName . '/');
        QUI\Utils\System\File::mkdir(USR_DIR . $projectName . '/');
        Manager::cleanup();
        Manager::$Standard = null;
        QUI\Cache\Manager::clearProjectsCache();
    }

    private static function assertDatabaseIsAvailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
        } catch (Throwable $Exception) {
            throw new RuntimeException(
                'QUIQQER database is not available: ' . $Exception->getMessage(),
                0,
                $Exception
            );
        }
    }

    private static function getAvailableProjectName(): string
    {
        $existingProjects = [];

        try {
            $existingProjects = array_keys(Manager::getConfig()->toArray());
        } catch (Throwable) {
        }

        for ($index = 0; $index < 100; $index++) {
            $projectName = self::BASE_PROJECT_NAME . ($index === 0 ? '' : '_' . $index);

            if (!in_array($projectName, $existingProjects, true)) {
                return $projectName;
            }
        }

        return self::BASE_PROJECT_NAME . '_' . substr(md5((string)microtime(true)), 0, 8);
    }
}
