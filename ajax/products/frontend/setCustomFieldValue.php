<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_setCustomFieldValues
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Get the fields for a frontend product
 *
 * @param integer $productId - Product-ID
 * @param array $fields
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_setCustomFieldValue',
    function ($productId, $field, $value) {
        $Product = Products::getProduct($productId);

        $Field = $Product->getField($field);
        $Field->setValue($value);

        return $Field->getValue();
    },
    ['productId', 'fields']
);
