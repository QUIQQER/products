<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\AbstractType
 */

namespace QUI\ERP\Products\Product\Types;

use QUI\ERP\Products\Interfaces\ProductTypeInterface;
use QUI\ERP\Products\Product\Product;
use QUI\Locale;

/**
 * Class AbstractType
 */
abstract class AbstractType extends Product implements ProductTypeInterface
{
    /**
     * Returns the backend panel control
     */
    public static function getTypeBackendPanel(): string
    {
        return 'package/quiqqer/products/bin/controls/products/Product';
    }

    /**
     * @return bool
     */
    public static function isTypeSelectable(): bool
    {
        return true;
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    abstract public static function getTypeTitle(null | Locale $Locale = null): string;

    /**
     * @param Locale|null $Locale
     * @return string
     */
    abstract public static function getTypeDescription(null | Locale $Locale = null): string;
}
