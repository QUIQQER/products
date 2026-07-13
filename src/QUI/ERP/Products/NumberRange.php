<?php

namespace QUI\ERP\Products;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use QUI;
use QUI\ERP\Api\NumberRangeInterface;

/**
 * Class Order
 * - Order range
 */
class NumberRange implements NumberRangeInterface
{
    /**
     * @param null|QUI\Locale $Locale
     *
     * @return string
     */
    public function getTitle(null | QUI\Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'NumberRange.title');
    }

    /**
     * Return the current start range value
     *
     * @return int
     * @throws QUI\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRange(): int
    {
        $QueryBuilder = QUI::getQueryBuilder();

        return (int)$QueryBuilder
            ->select('COALESCE(MAX(' . QUI\Utils\Doctrine::quoteIdentifier('id') . '), 0) + 1')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(
                QUI\ERP\Products\Utils\Tables::getProductTableName()
            ))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param int $range
     */
    public function setRange(int $range): void
    {
        $tableName = QUI\ERP\Products\Utils\Tables::getProductTableName();
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();

        if ($Platform instanceof AbstractMySQLPlatform) {
            $Connection->executeStatement(
                'ALTER TABLE ' . QUI\Utils\Doctrine::quoteIdentifier($tableName) . ' AUTO_INCREMENT = ' . $range
            );
            return;
        }

        if ($Platform instanceof PostgreSQLPlatform) {
            $sequence = $Connection->fetchOne(
                'SELECT pg_get_serial_sequence(:tableName, :columnName)',
                ['tableName' => $tableName, 'columnName' => 'id']
            );

            if (!is_string($sequence) || $sequence === '') {
                throw new QUI\Exception('Product ID sequence is unavailable.');
            }

            $Connection->executeStatement(
                'SELECT setval(CAST(:sequence AS regclass), :range, false)',
                ['sequence' => $sequence, 'range' => $range]
            );
            return;
        }

        if ($Platform instanceof SQLitePlatform) {
            $Connection->update('sqlite_sequence', ['seq' => $range - 1], ['name' => $tableName]);
            return;
        }

        throw new QUI\Exception('Setting the product number range is unsupported by this database platform.');
    }
}
