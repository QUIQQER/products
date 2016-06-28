<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_setFieldValues
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_setFieldValues',
    function ($productId, $fields) {
        $Product = Products::getProduct($productId);
        $fields  = json_decode($fields);

        foreach ($fields as $field => $value) {
            $Field = $Product->getField($field);
            $Field->setValue($value);
        }
    },
    array('productId', 'fields')
);
