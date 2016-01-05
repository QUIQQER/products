<?php

/**
 * This file contains QUI\ERP\Products\Handler\Products
 */
namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Products
 * get and add new products
 *
 * @package QUI\ERP\Products\Handler
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
     * @return QUI\ERP\Products\Product\Modell
     *
     * @throw QUI\Exception
     */
    public static function getProduct($pid)
    {
        if (isset(self::$list[$pid])) {
            return self::$list[$pid];
        }

        $Product          = new QUI\ERP\Products\Product\Modell($pid);
        self::$list[$pid] = $Product;

        return $Product;
    }

    /**
     * Create a new Product
     *
     * @param array $fields - optional, list of fields
     * @param string $productNo - optional, own product number
     *
     * @return QUI\ERP\Products\Product\Modell
     */
    public static function createProduct($fields = array(), $productNo = '')
    {
        QUI\Rights\Permission::checkPermission('product.create');


        QUI::getDataBase()->insert(
            QUI\ERP\Products\Tables::getProductTable(),
            array(
                'productNo' => $productNo,
                'data' => $fields
            )
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        return self::getProduct($newId);
    }

    /**
     * Return a list of products
     * if $queryParams is empty, all fields are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array
     */
    public static function getProducts($queryParams = array())
    {
        $query['from'] = QUI\ERP\Products\Tables::getProductTable();

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        if (isset($queryParams['order'])) {
            $query['order'] = $queryParams['order'];
        }

        foreach ($data as $entry) {
            try {
                $result[] = self::getProduct($entry['id']);
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * @param integer $pid
     */
    public static function deleteProduct($pid)
    {
        self::getProduct($pid)->delete();
    }
}
