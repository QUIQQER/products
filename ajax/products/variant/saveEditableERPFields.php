<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_saveEditableERPFields
 */

/**
 * Set the global editable variant fields
 *
 * @param array $fields - field ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_saveEditableERPFields',
    function ($fields) {
        QUI\ERP\Products\Handler\Products::setGlobalEditableVariantFields(
            json_decode($fields, true)
        );
    },
    ['fields'],
    'Permission::checkAdminUser'
);
