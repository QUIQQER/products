<?php

namespace QUI\ERP\Products\Product\Types;

use QUI;

/**
 * Class DigitalProduct
 *
 * Represents a non-physical product that does not require shipping.
 */
class DigitalProduct extends AbstractType
{
    /**
     * @param QUI\Locale $Locale
     * @return string
     */
    public static function getTypeTitle($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product_type.DigitalProduct.title');
    }

    /**
     * @param QUI\Locale $Locale
     * @return string
     */
    public static function getTypeDescription($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product_type.DigitalProduct.description');
    }
}
