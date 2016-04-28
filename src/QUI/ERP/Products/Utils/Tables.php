<?php

/**
 * This file contains QUI\ERP\Products\Utils\Tables
 */
namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class Tables
 * Table Helper
 */
class Tables
{
    /**
     * Products table
     */
    const PRODUCTS = 'products';

    /**
     * Products table
     */
    const PRODUCTS_CACHE = 'products_cache';

    /**
     * Field table
     */
    const FIELDS = 'product_fields';

    /**
     * Categories table
     */
    const CATEGORIES = 'product_categories';

    /**
     * Return the product database table name
     *
     * @return string
     */
    public static function getProductTableName()
    {
        return QUI::getDBTableName(self::PRODUCTS);
    }

    /**
     * @return string
     */
    public static function getProductCacheTableName()
    {
        return QUI::getDBTableName(self::PRODUCTS_CACHE);
    }

    /**
     * Return the field database table name
     *
     * @return string
     */
    public static function getFieldTableName()
    {
        return QUI::getDBTableName(self::FIELDS);
    }

    /**
     * Return the categories database table name
     *
     * @return string
     */
    public static function getCategoryTableName()
    {
        return QUI::getDBTableName(self::CATEGORIES);
    }

}
