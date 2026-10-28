<?php

namespace QUITests\ERP\Products\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\FloatType;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Utils\Database;
use QUI\ERP\Products\Utils\SearchTextCollation;
use QUI\Utils\Text\XML;
use ReflectionMethod;

class SearchTextCollationDatabaseTest extends TestCase
{
    private Connection $Connection;
    private ?string $tableName = null;

    protected function setUp(): void
    {
        $this->Connection = QUI::getDataBaseConnection();

        SearchTextCollation::onHeaderLoaded();

        $this->tableName = 'phpunit_products_collation_' . bin2hex(random_bytes(6));
        $Table = new Table($this->tableName);
        $Table->addColumn('id', 'integer');
        $Table->setPrimaryKey(['id']);
        $Table->addColumn('title', 'string', [
            'length' => 128,
            'default' => 'Default title',
            'comment' => 'Keep the title definition'
        ]);
        $Table->addColumn('description', 'text', ['notnull' => false]);
        $Table->addColumn('productNo', 'string', ['length' => 73, 'notnull' => false]);
        $Table->addColumn('tags', 'text', ['notnull' => false]);
        $Table->addColumn('F4', 'text', ['notnull' => false]);
        $Table->addColumn('F7', 'float', ['notnull' => false]);
        $Table->addColumn('type', 'string', ['length' => 255, 'default' => 'Product']);
        $Table->addIndex(['productNo']);

        $Table->addColumn('F19', 'string', [
            'length' => 64,
            'notnull' => false,
            'default' => 'Original value'
        ]);

        if ($this->Connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $Table->addOption('charset', 'utf8mb4');
            $Table->addOption('collation', 'utf8mb4_general_ci');

            foreach (['title', 'description', 'productNo', 'tags', 'F4', 'type', 'F19'] as $name) {
                $charset = $name === 'F19' ? 'latin1' : 'utf8mb4';
                $Table->getColumn($name)->setPlatformOption('charset', $charset);
                $Table->getColumn($name)->setPlatformOption('collation', $charset . '_general_ci');
            }
        }

        $this->Connection->createSchemaManager()->createTable($Table);
    }

    protected function tearDown(): void
    {
        if ($this->tableName !== null) {
            $SchemaManager = $this->Connection->createSchemaManager();

            if ($SchemaManager->tablesExist([$this->tableName])) {
                $SchemaManager->dropTable($this->tableName);
            }
        }
    }

    public function testMigrationDistinguishesUmlautsPreservesCaseFoldingAndRetainsData(): void
    {
        $words = [
            1 => 'schon',
            2 => 'schön',
            3 => 'drucken',
            4 => 'drücken',
            5 => 'Muller',
            6 => 'Müller',
            7 => 'Ärger',
            8 => 'Öl',
            9 => 'Tür'
        ];

        foreach ($words as $id => $word) {
            $this->Connection->insert($this->tableName, [
                'id' => $id,
                'title' => $word,
                'description' => $word,
                'productNo' => $word,
                'tags' => ',' . $word . ',',
                'F4' => $word,
                'F19' => $word,
                'F7' => $id * 1.25
            ]);
        }

        $SchemaManager = $this->Connection->createSchemaManager();
        $Before = $SchemaManager->introspectTable($this->tableName);
        $quotedTable = $this->Connection->getDatabasePlatform()->quoteSingleIdentifier($this->tableName);
        $rowsBefore = $this->Connection->fetchAllAssociative('SELECT * FROM ' . $quotedTable . ' ORDER BY id');

        SearchTextCollation::migrate($this->Connection, $this->tableName);
        self::assertSame(0, SearchTextCollation::migrate($this->Connection, $this->tableName));

        $searches = [
            'schon' => 1,
            'SCHÖN' => 2,
            'drucken' => 3,
            'DRÜCKEN' => 4,
            'Muller' => 5,
            'MÜLLER' => 6,
            'ärger' => 7,
            'ÄRGER' => 7,
            'öl' => 8,
            'ÖL' => 8,
            'tür' => 9,
            'TÜR' => 9,
            'arger' => null,
            'ol' => null,
            'tur' => null
        ];

        foreach (['title', 'description', 'productNo', 'tags', 'F4', 'F19'] as $column) {
            foreach ($searches as $word => $expectedId) {
                self::assertSame(
                    $expectedId === null ? [] : [$expectedId],
                    $this->search($column, $word),
                    $column . ': ' . $word
                );
            }
        }

        $After = $SchemaManager->introspectTable($this->tableName);

        foreach ($Before->getColumns() as $Column) {
            $name = $Column->getName();
            $expected = $Column->toArray();

            $actual = $After->getColumn($name)->toArray();

            if (in_array($name, ['title', 'description', 'productNo', 'tags', 'F4', 'F19'], true)) {
                unset($expected['collation'], $actual['collation']);
            }

            self::assertSame($expected, $actual, $name);
        }

        self::assertSame(array_keys($Before->getIndexes()), array_keys($After->getIndexes()));
        self::assertSame(
            $rowsBefore,
            $this->Connection->fetchAllAssociative('SELECT * FROM ' . $quotedTable . ' ORDER BY id')
        );

        $ranked = $this->Connection->fetchFirstColumn(
            'SELECT id FROM ' . $quotedTable
            . ' WHERE id IN (1, 2) ORDER BY CASE WHEN LOWER(title) = LOWER(?) THEN 1 ELSE 0 END DESC, id',
            ['schön']
        );
        self::assertSame([2, 1], array_map('intval', $ranked));
    }

    public function testNewSearchColumnsDistinguishUmlautsAndNumericColumnsStayNumeric(): void
    {
        Database::addColumn($this->tableName, 'F20', 'VARCHAR(85)', caseSensitiveText: true);
        Database::addColumn($this->tableName, 'F21', 'TEXT', caseSensitiveText: true);
        Database::addColumn($this->tableName, 'F22', 'DOUBLE', caseSensitiveText: true);
        Database::addColumn($this->tableName, 'F20', 'VARCHAR(85)', caseSensitiveText: true);
        $Table = $this->Connection->createSchemaManager()->introspectTable($this->tableName);

        $this->Connection->insert($this->tableName, ['id' => 1, 'F20' => 'Muller', 'F21' => 'schon']);
        $this->Connection->insert($this->tableName, ['id' => 2, 'F20' => 'Müller', 'F21' => 'schön']);

        self::assertSame([1], $this->search('F20', 'MULLER'));
        self::assertSame([2], $this->search('F20', 'MÜLLER'));
        self::assertSame([1], $this->search('F21', 'SCHON'));
        self::assertSame([2], $this->search('F21', 'SCHÖN'));
        self::assertSame(85, $Table->getColumn('F20')->getLength());
        self::assertInstanceOf(FloatType::class, $Table->getColumn('F22')->getType());
    }

    public function testPortableXmlImportAndRepeatedSetupRetainTheMigratedCollations(): void
    {
        $definition = XML::getDataBaseFromXml(dirname(__DIR__, 5) . '/database.xml');
        $cacheDefinition = null;

        foreach ($definition['globals'] as $table) {
            if ($table['suffix'] === 'products_cache') {
                $cacheDefinition = $table;
                break;
            }
        }

        self::assertNotNull($cacheDefinition);
        $SchemaManager = $this->Connection->createSchemaManager();
        $SchemaManager->dropTable($this->tableName);
        $Import = new ReflectionMethod(XML::class, 'importDataBaseTable');
        $Import->invoke(null, $this->tableName, $cacheDefinition);

        SearchTextCollation::migrate($this->Connection, $this->tableName);
        Database::addColumn($this->tableName, 'F4', 'TEXT', caseSensitiveText: true);
        $Import->invoke(null, $this->tableName, $cacheDefinition);
        self::assertSame(0, SearchTextCollation::migrate($this->Connection, $this->tableName));

        foreach ([1 => 'schon', 2 => 'schön', 3 => 'Müller'] as $id => $word) {
            $this->Connection->insert($this->tableName, [
                'id' => $id,
                'type' => 'Product',
                'lang' => 'de',
                'productNo' => $word,
                'title' => $word,
                'description' => $word,
                'tags' => ',' . $word . ',',
                'F4' => $word
            ]);
        }

        foreach (['productNo', 'title', 'description', 'tags', 'F4'] as $column) {
            self::assertSame([1], $this->search($column, 'SCHON'), $column);
            self::assertSame([2], $this->search($column, 'SCHÖN'), $column);
            self::assertSame([3], $this->search($column, 'MÜLLER'), $column);
        }
    }

    public function testCaseFoldingPreservesNullAndWorksAfterRepeatedInitialization(): void
    {
        SearchTextCollation::onHeaderLoaded();
        SearchTextCollation::onHeaderLoaded();

        self::assertSame('ärger öl müller', $this->Connection->fetchOne('SELECT LOWER(?)', ['ÄRGER ÖL MÜLLER']));
        self::assertSame('ascii', $this->Connection->fetchOne('SELECT LOWER(?)', ['ASCII']));
        self::assertNull($this->Connection->fetchOne('SELECT LOWER(NULL)'));
    }

    /**
     * @return list<int>
     */
    private function search(string $column, string $word): array
    {
        $Platform = $this->Connection->getDatabasePlatform();
        $quotedTable = $Platform->quoteSingleIdentifier($this->tableName);
        $Column = $this->Connection->createSchemaManager()->introspectTable($this->tableName)->getColumn($column);
        $quotedColumn = $Platform->quoteSingleIdentifier($Column->getName());
        $ids = $this->Connection->fetchFirstColumn(
            'SELECT id FROM ' . $quotedTable
            . ' WHERE LOWER(' . $quotedColumn . ') LIKE LOWER(?) ORDER BY id',
            ['%' . $word . '%']
        );

        return array_map('intval', $ids);
    }
}
