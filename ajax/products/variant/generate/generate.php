<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_generate_generate
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_generate',
    function ($productId, $fields) {
        $Product = Products::getProduct($productId);
        $fields  = \json_decode($fields, true);

        if (!($Product instanceof VariantParent)) {
            return;
        }

        $Product->generateVariants($fields);
    },
    ['productId', 'fields'],
    'Permission::checkAdminUser'
);
