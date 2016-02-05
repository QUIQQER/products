<?php

/**
 * This file contains package_quiqqer_products_ajax_products_create
 */

/**
 * Create a new product
 *
 * @param string $categories - JSON categories
 * @param string $fields - JSON fields
 * @param string $productNo - Several product number
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_create',
    function ($categories, $fields, $productNo) {
        $fields     = json_decode($fields, true);
        $categories = json_decode($categories, true);

        $Products = new QUI\ERP\Products\Handler\Products();
        $Product  = $Products->createProduct($categories, $fields, $productNo);

        return $Product->getAttributes();
    },
    array('categories', 'fields', 'productNo'),
    'Permission::checkAdminUser'
);
