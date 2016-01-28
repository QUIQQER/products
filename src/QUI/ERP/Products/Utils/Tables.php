<?php

/**
 * This file contains QUI\ERP\Products\Tables\Utils
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
