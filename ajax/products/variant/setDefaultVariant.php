<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_setDefaultVariant
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_setDefaultVariant',
    function ($productId, $variantId) {
        $Product = Products::getProduct($productId);

        if ($Product instanceof VariantParent) {
            if (empty($variantId)) {
                $Product->unsetDefaultVariant();
            } else {
                $Product->setDefaultVariant($variantId);
            }

            $Product->save();
        }
    },
    ['productId', 'variantId'],
    'Permission::checkAdminUser'
);
