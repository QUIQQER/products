<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getFieldTypes
 */

/**
 * Returns all available field types
 *
 * @param integer $fieldId
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getSearchTypesForField',
    function ($fieldId) {
        $Field = \QUI\ERP\Products\Handler\Fields::getField((int)$fieldId);

        return $Field->getSearchTypes();
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
