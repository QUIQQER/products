<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_generate_create
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Add a variant
 *
 * @param integer $productId - Product-ID
 * @return int|false
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_create',
    function ($productId) {
        $Product = Products::getProduct($productId);

        if (!($Product instanceof VariantParent)) {
            return false;
        }

        $Variant = $Product->createVariant();
        $Variant->save();

        return $Variant->getId();
    },
    ['productId'],
    'Permission::checkAdminUser'
);
