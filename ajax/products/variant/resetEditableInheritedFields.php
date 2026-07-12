<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_resetEditableInheritedFields
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Reset inherited fields
 *
 * @param integer $productId - Product-ID
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_variant_resetEditableInheritedFields',
    function ($productId) {
        $Product = Products::getProduct($productId);

        if ($Product instanceof VariantChild) {
            $Parent = $Product->getParent();

            if (!$Parent instanceof VariantParent) {
                return;
            }

            $Product = $Parent;
        }

        $Product->setAttribute('editableVariantFields', false);
        $Product->setAttribute('inheritedVariantFields', false);

        $Product->save();
    },
    ['productId'],
    'Permission::checkAdminUser'
);
