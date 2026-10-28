<?php

namespace QUI\ERP\Products\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use PDO;
use QUI;
use QUI\ERP\Products\Search\Cache;
use SQLite3;

/**
 * Keep product searches accent-sensitive while folding Unicode letter case with LOWER().
 */
final class SearchTextCollation
{
    /**
     * SQLite functions belong to the connection, so register them on every application startup.
     */
    public static function onHeaderLoaded(): void
    {
        self::configureConnection(QUI::getDataBaseConnection());
    }

    /**
     * SQLite's built-in LOWER() only handles ASCII. Match the Unicode behavior of the other drivers.
     */
    public static function configureConnection(Connection $Connection): void
    {
        if (!$Connection->getDatabasePlatform() instanceof SQLitePlatform) {
            return;
        }

        $Native = $Connection->getNativeConnection();
        $lower = static fn (?string $value): ?string => $value === null ? null : mb_strtolower($value, 'UTF-8');

        if ($Native instanceof PDO) {
            $Native->sqliteCreateFunction('lower', $lower, 1, PDO::SQLITE_DETERMINISTIC);
        } elseif ($Native instanceof SQLite3) {
            $Native->createFunction('lower', $lower, 1, SQLITE3_DETERMINISTIC);
        }
    }

    /**
     * Called after database.xml is imported, and after dynamic fields are checked during setup.
     */
    public static function setup(): void
    {
        self::onHeaderLoaded();
        $changedColumns = self::migrate(
            QUI::getDataBaseConnection(),
            Tables::getProductCacheTableName()
        );

        if ($changedColumns > 0) {
            Cache::clear();
        }
    }

    /**
     * Apply all necessary collation changes in one ALTER TABLE, preserving the column definitions.
     *
     * @return int Number of changed columns; zero when the schema already matches or is not MySQL.
     */
    public static function migrate(Connection $Connection, string $tableName): int
    {
        if (!$Connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return 0;
        }

        $SchemaManager = $Connection->createSchemaManager();

        if (!$SchemaManager->tablesExist([$tableName])) {
            return 0;
        }

        $Table = $SchemaManager->introspectTable($tableName);
        $UpdatedTable = clone $Table;

        foreach ($UpdatedTable->getColumns() as $Column) {
            $name = $Column->getName();

            if (
                !in_array($name, ['productNo', 'title', 'description', 'tags'], true)
                && !preg_match('/^F[1-9][0-9]*$/', $name)
            ) {
                continue;
            }

            self::configureColumn($Connection, $Column);
        }

        $Diff = $SchemaManager->createComparator()->compareTables($Table, $UpdatedTable);

        if ($Diff->isEmpty()) {
            return 0;
        }

        $SchemaManager->alterTable($Diff);

        return count($Diff->getChangedColumns());
    }

    /**
     * Set a binary collation for text, retaining an existing column's character set.
     * New search columns use UTF-8, as does the standard QUIQQER schema.
     */
    public static function configureColumn(Connection $Connection, Column $Column): void
    {
        if (
            !$Connection->getDatabasePlatform() instanceof AbstractMySQLPlatform
            || !in_array(Type::lookupName($Column->getType()), ['string', 'text', 'ascii_string'], true)
        ) {
            return;
        }

        $charset = $Column->getPlatformOptions()['charset'] ?? 'utf8mb4';
        $Column->setPlatformOption('charset', $charset);
        $Column->setPlatformOption('collation', $charset . '_bin');
    }
}
