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
    'package_quiqqer_products_ajax_products_variant_saveOverwriteableFields',
    function ($productId, $overwriteable) {
        $Product = Products::getProduct($productId);

        $Product->setAttribute(
            'overwriteableVariantFields',
            \json_decode($overwriteable, true)
        );

        $Product->save();
    },
    ['productId', 'overwriteable'],
    'Permission::checkAdminUser'
);
