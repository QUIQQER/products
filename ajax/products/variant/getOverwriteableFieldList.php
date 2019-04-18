<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getOverwriteableFieldList
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getOverwriteableFieldList',
    function ($productId) {
        $Product       = Products::getProduct($productId);
        $overwriteable = $Product->getAttribute('overwriteableVariantFields');

        // fields
        $fields = $Product->getFields();
        $fields = \array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getAttributes();
        }, $fields);


        return [
            'overwriteable' => $overwriteable,
            'fields'        => $fields
        ];
    },
    ['productId'],
    'Permission::checkAdminUser'
);
