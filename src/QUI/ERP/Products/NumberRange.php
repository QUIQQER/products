<?php

namespace QUI\ERP\Products;

use QUI;
use QUI\Database\Exception;
use QUI\ERP\Api\NumberRangeInterface;

use function is_numeric;

/**
 * Class Order
 * - Order range
 *
 * @package QUI\ERP\Order\NumberRanges
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
     * @throws Exception
     */
    public function getRange(): int
    {
        $Table = QUI::getDataBase()->table();

        return $Table->getAutoIncrementIndex(QUI\ERP\Products\Utils\Tables::getProductTableName());
    }

    /**
     * @param int $range
     */
    public function setRange(int $range): void
    {
        if (!is_numeric($range)) {
            return;
        }

        $PDO = QUI::getDataBase()->getPDO();
        $tableName = QUI\ERP\Products\Utils\Tables::getProductTableName();

        $Statement = $PDO->prepare(
            "ALTER TABLE $tableName AUTO_INCREMENT = " . (int)$range
        );

        $Statement->execute();
    }
}
