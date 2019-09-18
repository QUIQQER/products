<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getProductsFromCategory
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\Tables;
use QUI\ERP\Products\Handler\Fields;

/**
 * Update all product fields with the category id fields
 *
 * @param string $categoryId - Category ID
 * @param string $params
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_getProductsFromCategory',
    function ($categoryId, $params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryId);
        $Grid       = new QUI\Utils\Grid();

        $products = [];

        $productIds = $Category->getProductIds(
            $Grid->parseDBParams(\json_decode($params, true))
        );

        // Get data from cache
        $result = QUI::getDataBase()->fetch([
            'select' => [
                'id',
                'title',
                'description',
                'F'.Fields::FIELD_PRICE
            ],
            'from'   => Tables::getProductCacheTableName(),
            'where'  => [
                'id'   => [
                    'type'  => 'IN',
                    'value' => $productIds
                ],
                'lang' => QUI::getLocale()->getCurrent()
            ]
        ]);

        foreach ($result as $row) {
            $products[] = [
                'id'          => $row['id'],
                'title'       => $row['title'],
                'description' => $row['description'],
                'price'       => $row['F'.Fields::FIELD_PRICE]
            ];
        }

        return $Grid->parseResult($products, $Category->countProducts());
    },
    ['categoryId', 'params'],
    'Permission::checkAdminUser'
);
