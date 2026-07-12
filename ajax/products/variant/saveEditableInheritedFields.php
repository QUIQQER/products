<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_saveEditableInheritedFields
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Save editable fields
 *
 * @param integer $productId - Product-ID
 * @param mixed $editable
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_variant_saveEditableInheritedFields',
    function ($productId, $editable, $inherited) {
        $Product = Products::getProduct($productId);

        if ($Product instanceof VariantChild) {
            $Parent = $Product->getParent();

            if (!$Parent instanceof VariantParent) {
                return;
            }

            $Product = $Parent;
        }

        $Product->setAttribute('editableVariantFields', json_decode($editable, true));
        $Product->setAttribute('inheritedVariantFields', json_decode($inherited, true));

        $Product->save();
    },
    ['productId', 'editable', 'inherited'],
    'Permission::checkAdminUser'
);
