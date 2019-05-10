<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_saveOverwritableFields
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_saveOverwritableFields',
    function ($productId, $overwritable) {
        $Product = Products::getProduct($productId);

        $Product->setAttribute(
            'overwritableVariantFields',
            \json_decode($overwritable, true)
        );

        $Product->save();
    },
    ['productId', 'overwritable'],
    'Permission::checkAdminUser'
);
