<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\Product
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;

/**
 * Class Product
 * - Default Product Type
 *
 * @package QUI\ERP\Products\Product\Types
 */
class Product extends AbstractType
{
    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeTitle($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.standard.title');
    }

    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeDescription($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.standard.description');
    }
}
