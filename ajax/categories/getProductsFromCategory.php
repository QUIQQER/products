<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getProductsFromCategory
 */

use QUI\ERP\Products\Handler\Products;

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

        $products = array();

        $productIds = $Category->getProductIds(
            $Grid->parseDBParams(json_decode($params, true))
        );

        foreach ($productIds as $productId) {
            $Product    = Products::getProduct($productId);
            $attributes = $Product->getAttributes();

            $attributes['title']       = $Product->getTitle();
            $attributes['description'] = $Product->getDescription();

            $products[] = $attributes;
        }

        return $Grid->parseResult($products, $Category->countProducts());
    },
    array('categoryId', 'params'),
    'Permission::checkAdminUser'
);
