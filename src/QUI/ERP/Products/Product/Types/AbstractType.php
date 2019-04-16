<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\AbstractType
 */

namespace QUI\ERP\Products\Product\Types;

use QUI\ERP\Products\Interfaces\ProductTypeInterface;
use QUI\ERP\Products\Product\Product;

/**
 * Class AbstractType
 */
abstract class AbstractType extends Product implements ProductTypeInterface
{
    /**
     * Returns the backend panel control
     */
    public static function getTypeBackendPanel()
    {
        return 'package/quiqqer/products/bin/controls/products/Product';
    }

    /**
     * @return bool|mixed
     */
    public static function isTypeSelectable()
    {
        return true;
    }

    /**
     * @param null $Locale
     * @return mixed
     */
    abstract public static function getTypeTitle($Locale = null);

    /**
     * @param null $Locale
     * @return mixed
     */
    abstract public static function getTypeDescription($Locale = null);
}