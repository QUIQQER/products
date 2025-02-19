<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\Product
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\Locale;

/**
 * Class Product
 * - Default Product Type
 *
 * @package QUI\ERP\Products\Product\Types
 */
class Product extends AbstractType
{
    public static function getTypeTitle(null | QUI\Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.standard.title');
    }

    public static function getTypeDescription(null | QUI\Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.standard.description');
    }
}
