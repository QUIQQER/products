<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getOverwritableFieldList
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getOverwritableFieldList',
    function ($productId) {
        $Product      = Products::getProduct($productId);
        $overwritable = $Product->getAttribute('overwritableVariantFields');

        if ($overwritable === false) {
            // @todo get erp fields
        }

        // fields
        $fields = $Product->getFields();
        $fields = \array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getAttributes();
        }, $fields);


        return [
            'overwritable' => $overwritable,
            'fields'        => $fields
        ];
    },
    ['productId'],
    'Permission::checkAdminUser'
);
