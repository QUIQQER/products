<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_get
 */

/**
 * Returns a field
 *
 * @param string $fieldId - Field-ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_get',
    function ($fieldId) {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $Field  = $Fields->getField($fieldId);

        return $Field->getAttributes();
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
