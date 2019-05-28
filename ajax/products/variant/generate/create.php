<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_generate_create
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Add / create a variant
 *
 * @param integer $productId - Product-ID
 * @return int|false
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_create',
    function ($productId, $fields) {
        $Product = Products::getProduct($productId);
        $fields  = \json_decode($fields, true);

        if (!($Product instanceof VariantParent)) {
            return false;
        }

        return $Product->generateVariant($fields)->getId();
    },
    ['productId', 'fields'],
    'Permission::checkAdminUser'
);
