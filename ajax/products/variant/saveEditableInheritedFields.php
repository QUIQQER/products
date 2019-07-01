<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_saveEditableInheritedFields
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Save editable fields
 *
 * @param integer $productId - Product-ID
 * @param $editable
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_saveEditableInheritedFields',
    function ($productId, $editable, $inherited) {
        $Product = Products::getProduct($productId);

        if ($Product instanceof \QUI\ERP\Products\Product\Types\VariantChild) {
            $Product = $Product->getParent();
        }

        $Product->setAttribute('editableVariantFields', \json_decode($editable, true));
        $Product->setAttribute('inheritedVariantFields', \json_decode($inherited, true));

        $Product->save();
    },
    ['productId', 'editable', 'inherited'],
    'Permission::checkAdminUser'
);
