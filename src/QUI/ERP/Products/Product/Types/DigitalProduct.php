<?php

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\Locale;

/**
 * Class DigitalProduct
 *
 * Represents a non-physical product that does not require shipping.
 */
class DigitalProduct extends AbstractType
{
    /**
     * @param Locale|null $Locale
     * @return string
     */
    public static function getTypeTitle(QUI\Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product_type.DigitalProduct.title');
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public static function getTypeDescription(QUI\Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product_type.DigitalProduct.description');
    }
}
