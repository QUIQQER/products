<?php

namespace QUITests\ERP\Products;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\NumberRange;

class NumberRangeTest extends TestCase
{
    private ?Connection $oldConnection = null;

    protected function tearDown(): void
    {
        if ($this->oldConnection !== null) {
            $this->setConnection($this->oldConnection);
            $this->oldConnection = null;
        }

        parent::tearDown();
    }

    public function testSetRangeAdvancesSqliteIdentityUsingDbal(): void
    {
        $Connection = $this->createSqliteProductDatabase();
        $tableName = \QUI::getDBTableName('products');

        $Connection->insert($tableName, ['type' => 'existing-product']);

        $NumberRange = new NumberRange();
        self::assertSame(2, $NumberRange->getRange());

        $NumberRange->setRange(100);

        $QueryBuilder = $Connection->createQueryBuilder();
        $placeholderCount = $QueryBuilder
            ->select('COUNT(*)')
            ->from($Connection->quoteSingleIdentifier($tableName))
            ->where($QueryBuilder->expr()->eq(
                $Connection->quoteSingleIdentifier('type'),
                ':type'
            ))
            ->setParameter('type', '__number_range_placeholder__')
            ->executeQuery()
            ->fetchOne();

        self::assertSame(0, (int)$placeholderCount);

        $Connection->insert($tableName, ['type' => 'next-product']);

        self::assertSame(100, (int)$Connection->lastInsertId());
    }

    public function testSetRangeDoesNotChangeIdentityBelowPersistedProducts(): void
    {
        $Connection = $this->createSqliteProductDatabase();
        $tableName = \QUI::getDBTableName('products');

        $Connection->insert($tableName, ['id' => 10, 'type' => 'existing-product']);

        (new NumberRange())->setRange(5);
        $Connection->insert($tableName, ['type' => 'next-product']);

        self::assertSame(11, (int)$Connection->lastInsertId());
    }

    private function createSqliteProductDatabase(): Connection
    {
        $this->oldConnection = \QUI::getDataBaseConnection();
        $Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);

        $schema = new Schema();
        $Table = $schema->createTable(\QUI::getDBTableName('products'));
        $Table->addColumn('id', 'integer', ['autoincrement' => true]);
        $Table->addColumn('type', 'string', ['length' => 255]);
        $Table->setPrimaryKey(['id']);

        foreach ($schema->toSql($Connection->getDatabasePlatform()) as $statement) {
            $Connection->executeStatement($statement);
        }

        $this->setConnection($Connection);

        return $Connection;
    }

    private function setConnection(Connection $Connection): void
    {
        $Reflection = new \ReflectionClass(\QUI::class);
        $property = $Reflection->getProperty('QueryBuilder');
        $property->setAccessible(true);
        $property->setValue($Connection);
    }
}
