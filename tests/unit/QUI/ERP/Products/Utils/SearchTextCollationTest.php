<?php

namespace QUITests\ERP\Products\Unit\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Utils\SearchTextCollation;
use QUI\Utils\Text\XML;

class SearchTextCollationTest extends TestCase
{
    public function testEventsXmlRegistersConnectionInitializationAtRuntime(): void
    {
        $events = XML::getEventsFromXml(dirname(__DIR__, 6) . '/events.xml');
        $handlers = [];

        foreach ($events as $Event) {
            if ($Event->getAttribute('on') === 'onHeaderLoaded') {
                $handlers[] = $Event->getAttribute('fire');
            }
        }

        self::assertContains('\\' . SearchTextCollation::class . '::onHeaderLoaded', $handlers);
        self::assertIsCallable($handlers[0]);
    }

    public function testDatabaseXmlRegistersThePortablePostImportHook(): void
    {
        $definition = XML::getDataBaseFromXml(dirname(__DIR__, 6) . '/database.xml');

        self::assertContains('\\' . SearchTextCollation::class . '::setup', $definition['execute']);
        self::assertIsCallable($definition['execute'][0]);
        self::assertContains('products_cache', array_column($definition['globals'], 'suffix'));
    }

    #[DataProvider('platforms')]
    public function testNewTextColumnsUseBinaryCollationsOnlyOnMysql(AbstractPlatform $Platform): void
    {
        $Connection = $this->createMock(Connection::class);
        $Connection->method('getDatabasePlatform')->willReturn($Platform);
        $Text = new Column('F4', Type::getType('string'), [
            'length' => 73,
            'notnull' => false,
            'default' => 'Müller',
            'comment' => 'Keep column metadata'
        ]);
        $Number = new Column('F7', Type::getType('float'));

        SearchTextCollation::configureColumn($Connection, $Text);
        SearchTextCollation::configureColumn($Connection, $Number);

        if ($Platform instanceof AbstractMySQLPlatform) {
            self::assertSame('utf8mb4_bin', $Text->getPlatformOption('collation'));
            self::assertSame('utf8mb4', $Text->getPlatformOption('charset'));
        } else {
            self::assertSame([], $Text->getPlatformOptions());
        }

        self::assertSame([], $Number->getPlatformOptions());
        self::assertSame(73, $Text->getLength());
        self::assertFalse($Text->getNotnull());
        self::assertSame('Müller', $Text->getDefault());
        self::assertSame('Keep column metadata', $Text->getComment());
    }

    public static function platforms(): iterable
    {
        yield 'MySQL' => [new MySQLPlatform()];
        yield 'MariaDB' => [new MariaDBPlatform()];
        yield 'PostgreSQL' => [new PostgreSQLPlatform()];
        yield 'SQLite' => [new SQLitePlatform()];
    }

    #[DataProvider('otherPlatforms')]
    public function testOtherDatabasesAreNotInspectedOrAltered(AbstractPlatform $Platform): void
    {
        $Connection = $this->createMock(Connection::class);
        $Connection->method('getDatabasePlatform')->willReturn($Platform);
        $Connection->expects(self::never())->method('createSchemaManager');

        self::assertSame(0, SearchTextCollation::migrate($Connection, 'products_cache'));
    }

    public function testNativeCaseFoldingDoesNotOpenMysqlOrPostgresConnections(): void
    {
        foreach ([new MySQLPlatform(), new PostgreSQLPlatform()] as $Platform) {
            $Connection = $this->createMock(Connection::class);
            $Connection->method('getDatabasePlatform')->willReturn($Platform);
            $Connection->expects(self::never())->method('getNativeConnection');
            SearchTextCollation::configureConnection($Connection);
        }
    }

    public static function otherPlatforms(): iterable
    {
        yield 'PostgreSQL' => [new PostgreSQLPlatform()];
        yield 'SQLite' => [new SQLitePlatform()];
    }

    public function testMissingTableDoesNotTriggerAMigration(): void
    {
        $Connection = $this->createMock(Connection::class);
        $Connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $SchemaManager = $this->createMock(AbstractSchemaManager::class);
        $SchemaManager->method('tablesExist')->with(['products_cache'])->willReturn(false);
        $SchemaManager->expects(self::never())->method('alterTable');
        $Connection->method('createSchemaManager')->willReturn($SchemaManager);

        self::assertSame(0, SearchTextCollation::migrate($Connection, 'products_cache'));
    }

    public function testMigrationPreservesDefinitionsAndIsIdempotent(): void
    {
        $Platform = new MySQLPlatform();
        $Table = new Table('products_cache');
        $Table->addColumn('id', 'bigint');
        $Table->setPrimaryKey(['id']);

        foreach (['productNo', 'title', 'description', 'tags', 'F4', 'F19'] as $name) {
            $Column = $Table->addColumn($name, 'string', [
                'length' => 83,
                'notnull' => false,
                'default' => 'Default title',
                'comment' => 'Retain the field definition'
            ]);
            $charset = $name === 'F19' ? 'latin1' : 'utf8mb4';
            $Column->setPlatformOption('charset', $charset);
            $Column->setPlatformOption('collation', $charset . '_general_ci');
        }

        $Type = $Table->addColumn('type', 'string', ['length' => 255]);
        $Type->setPlatformOption('charset', 'utf8mb4');
        $Type->setPlatformOption('collation', 'utf8mb4_general_ci');
        $Table->addColumn('F7', 'float');
        $Table->addIndex(['productNo']);
        $OriginalTable = clone $Table;

        $SchemaManager = $this->createMock(AbstractSchemaManager::class);
        $SchemaManager->method('tablesExist')->willReturn(true);
        $SchemaManager->method('introspectTable')->willReturnCallback(static fn () => $Table);
        $SchemaManager->method('createComparator')->willReturn(new Comparator($Platform));
        $SchemaManager->expects(self::once())->method('alterTable')->willReturnCallback(
            static function (TableDiff $Diff) use ($Table, $Platform): void {
                self::assertCount(6, $Diff->getChangedColumns());
                self::assertCount(1, $Platform->getAlterTableSQL($Diff));
                self::assertSame([], $Diff->getDroppedColumns());
                self::assertSame([], $Diff->getAddedColumns());

                foreach ($Diff->getChangedColumns() as $name => $ColumnDiff) {
                    $Updated = $ColumnDiff->getNewColumn();
                    $Table->getColumn($name)->setPlatformOption(
                        'collation',
                        $Updated->getPlatformOption('collation')
                    );
                }
            }
        );
        $Connection = $this->createMock(Connection::class);
        $Connection->method('getDatabasePlatform')->willReturn($Platform);
        $Connection->method('createSchemaManager')->willReturn($SchemaManager);

        self::assertSame(6, SearchTextCollation::migrate($Connection, 'products_cache'));
        self::assertSame(0, SearchTextCollation::migrate($Connection, 'products_cache'));
        self::assertSame('latin1_bin', $Table->getColumn('F19')->getPlatformOption('collation'));
        self::assertSame('utf8mb4_general_ci', $Table->getColumn('type')->getPlatformOption('collation'));
        self::assertSame($OriginalTable->getColumn('F7')->toArray(), $Table->getColumn('F7')->toArray());

        foreach (['productNo', 'title', 'description', 'tags', 'F4', 'F19'] as $name) {
            $expected = $OriginalTable->getColumn($name)->toArray();
            $charset = $expected['charset'];
            $expected['collation'] = $charset . '_bin';
            self::assertSame($expected, $Table->getColumn($name)->toArray());
        }

        self::assertSame(array_keys($OriginalTable->getIndexes()), array_keys($Table->getIndexes()));
    }
}
