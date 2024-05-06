<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_resetEditableInheritedFields
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;

/**
 * Reset inherited fields
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_resetEditableInheritedFields',
    function ($productId) {
        $Product = Products::getProduct($productId);

        if ($Product instanceof VariantChild) {
            $Product = $Product->getParent();
        }

        $Product->setAttribute('editableVariantFields', false);
        $Product->setAttribute('inheritedVariantFields', false);

        $Product->save();
    },
    ['productId'],
    'Permission::checkAdminUser'
);
