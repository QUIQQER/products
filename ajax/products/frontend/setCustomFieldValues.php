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
    'package_quiqqer_products_ajax_products_frontend_setCustomFieldValues',
    function ($productId, $fields) {
        $Product = Products::getProduct($productId);
        $fields  = json_decode($fields, true);
        $result  = [];

        foreach ($fields as $field => $value) {
            try {
                $Field = $Product->getField($field);
                $Field->setValue($value);

                $result[$Field->getId()] = $Field->getValue();
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    },
    ['productId', 'fields']
);
