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
     * @return QUI\ERP\Products\Product\Product
     *
     * @throw QUI\Exception
     */
    public static function getProduct($pid)
    {
        if (isset(self::$list[$pid])) {
            return self::$list[$pid];
        }

        $Product          = new QUI\ERP\Products\Product\Product($pid);
        self::$list[$pid] = $Product;

        return $Product;
    }

    /**
     * Create a new Product
     *
     * @param array $fields - optional, list of fields
     * @param string $productNo - optional, own product number
     *
     * @return QUI\ERP\Products\Product\Product
     */
    public static function createProduct($fields = array(), $productNo = '')
    {
        QUI\Rights\Permission::checkPermission('product.create');


        QUI::getDataBase()->insert(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array(
                'productNo' => $productNo,
                'data' => $fields
            )
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        // translation - title
        try {
            QUI\Translator::addUserVar(
                'quiqqer/products',
                'products.product.' . $newId . '.title',
                array(
                    'datatype' => 'js,php'
                )
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());

            QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }

        return self::getProduct($newId);
    }

    /**
     * Return a list of products
     * if $queryParams is empty, all fields are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array
     */
    public static function getProducts($queryParams = array())
    {
        $query['from'] = QUI\ERP\Products\Utils\Tables::getProductTableName();

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
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
