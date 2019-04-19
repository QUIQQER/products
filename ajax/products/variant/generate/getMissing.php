<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getVariants
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_getMissing',
    function ($productId) {
        $Product = Products::getProduct($productId);
    },
    ['productId'],
    'Permission::checkAdminUser'
);
