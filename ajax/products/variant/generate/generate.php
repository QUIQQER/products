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
    function ($productId, $fields, $generationType) {
        $Product = Products::getProduct($productId);
        $fields  = \json_decode($fields, true);

        if (!($Product instanceof VariantParent)) {
            return;
        }

        switch ($generationType) {
            case 'reset':
                $generationType = VariantParent::GENERATION_TYPE_RESET;
                break;

            default:
            case 'create-only-new-one':
                $generationType = VariantParent::GENERATION_TYPE_ADD;
                break;
        }

        $Product->generateVariants($fields, $generationType);
    },
    ['productId', 'fields', 'generationType'],
    'Permission::checkAdminUser'
);
