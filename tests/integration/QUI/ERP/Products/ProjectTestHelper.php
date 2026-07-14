<?php

namespace QUITests\ERP\Products\Integration;

use QUI;
use QUI\Permissions\Permission;
use QUI\Projects\Manager;
use QUI\Projects\Project;
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
                Manager::createProject($projectName, self::LANGUAGE, [self::LANGUAGE]);
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
