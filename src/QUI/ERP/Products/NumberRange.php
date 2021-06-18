<?php

namespace QUI\ERP\Products;

use QUI;
use QUI\ERP\Api\NumberRangeInterface;

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
    public function getTitle($Locale = null)
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
     */
    public function getRange()
    {
        $Table = QUI::getDataBase()->table();

        return $Table->getAutoIncrementIndex(QUI\ERP\Products\Utils\Tables::getProductTableName());
    }

    /**
     * @param int $range
     */
    public function setRange($range)
    {
        if (!\is_numeric($range)) {
            return;
        }

        $PDO       = QUI::getDataBase()->getPDO();
        $tableName = QUI\ERP\Products\Utils\Tables::getProductTableName();

        $Statement = $PDO->prepare(
            "ALTER TABLE {$tableName} AUTO_INCREMENT = ".(int)$range
        );

        $Statement->execute();
    }
}
