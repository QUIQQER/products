<?php

/**
 * This file contains QUI\Products\Tables
 */
namespace QUI\Products;

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
     * Return the product database table name
     *
     * @return string
     */
    public static function getProductTable()
    {
        return QUI::getDBTableName(self::PRODUCTS);
    }

    /**
     * Return the field database table name
     *
     * @return string
     */
    public static function getFieldTable()
    {
        return QUI::getDBTableName(self::FIELDS);
    }
}
