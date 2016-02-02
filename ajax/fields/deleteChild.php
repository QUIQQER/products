<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_deleteChild
 */

/**
 * Delete a field
 *
 * @param string $fieldId - Field-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_deleteChild',
    function ($fieldId) {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $Field  = $Fields->getField($fieldId);

        $Field->delete();
    },
    array('fieldId'),
    'Permission::checkAdminUser'
);
