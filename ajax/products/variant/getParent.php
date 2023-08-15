<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getParent
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * R
 *
 * @param integer $productId - Product-ID
 * @param string $options - JSON Array - Grid options
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getParent',
    function ($productId) {
        $Product = Products::getProduct($productId);

        if ($Product instanceof VariantParent) {
            return $Product->getId();
        }

        if ($Product instanceof VariantChild) {
            return $Product->getParent()->getId();
        }

        return false;
    },
    ['productId'],
    'Permission::checkAdminUser'
);
