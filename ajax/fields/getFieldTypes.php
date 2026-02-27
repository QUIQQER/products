<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getFieldTypes
 */

/**
 * Returns all available field types
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_getFieldTypes',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();

        return $Fields->getFieldTypes();
    },
    false,
    'Permission::checkAdminUser'
);
