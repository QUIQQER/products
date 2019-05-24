<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_saveOverwritableERPFields
 */

/**
 * Set the global overwritable variant fields
 *
 * @param array $fields - field ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_saveOverwritableERPFields',
    function ($fields) {
        QUI\ERP\Products\Handler\Products::setGlobalOverwritableVariantFields(
            json_decode($fields, true)
        );
    },
    ['fields'],
    'Permission::checkAdminUser'
);
