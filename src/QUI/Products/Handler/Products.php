<?php

/**
 * This file contains QUI\Products\Handler\Products
 */
namespace QUI\Products\Handler;

use QUI;


/**
 * Class Products
 * get and add new products
 *
 * @package QUI\Products\Handler
 */
class Products
{
    /**
     * List of internal products
     * @var array
     */
    private static $list = array();

    /**
     * @param integer $pid - Product-ID
     * @return QUI\Products\Product\Modell
     *
     * @throw QUI\Exception
     */
    public static function getProduct($pid)
    {
        if (isset(self::$list[$pid])) {
            return self::$list[$pid];
        }

        $Product          = new QUI\Products\Product\Modell($pid);
        self::$list[$pid] = $Product;

        return $Product;
    }

    /**
     * Create a new Product
     *
     * @param $attributes
     *
     * @return QUI\Products\Product\Modell
     */
    public static function createProduct($attributes)
    {
        QUI\Rights\Permission::checkPermission('product.create');

        $data   = array();
        $fields = QUI\Products\Tables::getProductTableFields();

        if (isset($attributes['id'])) {
            unset($attributes['id']);
        }

        foreach ($attributes as $key => $value) {
            if (in_array($key, $fields)) {
                $data[$key] = $fields;
            }
        }

        QUI::getDataBase()->insert(
            QUI\Products\Tables::getProductTable(),
            $data
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        return self::getProduct($newId);
    }

    /**
     * @param integer $pid
     */
    public static function deleteProduct($pid)
    {
        self::getProduct($pid)->delete();
    }
}
