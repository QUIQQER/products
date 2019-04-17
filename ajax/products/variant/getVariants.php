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
    'package_quiqqer_products_ajax_products_variant_getVariants',
    function ($productId) {
        $Product = Products::getProduct($productId);

        /* @var $Product VariantParent */
        if (!($Product instanceof VariantParent)) {
            return [];
        }

        $variants = $Product->getVariants();
        $variants = array_map(function ($Variant) {
            /* @var $Variant \QUI\ERP\Products\Product\Types\VariantChild */
            return $Variant->getAttributes();
        }, $variants);

        return $variants;
    },
    ['productId'],
    'Permission::checkAdminUser'
);
