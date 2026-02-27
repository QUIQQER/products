<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_saveEditableInheritedERPFields
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Set the global editable variant fields
 *
 * @param array $fields - field ids
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_variant_saveEditableInheritedERPFields',
    function ($editable, $inherited) {
        $editable = json_decode($editable, true);
        $inherited = json_decode($inherited, true);

        Products::setGlobalEditableVariantFields($editable);
        Products::setGlobalInheritedVariantFields($inherited);
    },
    ['editable', 'inherited'],
    'Permission::checkAdminUser'
);
