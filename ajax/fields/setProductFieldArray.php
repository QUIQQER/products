<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_setProductFieldArray
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Set the product field array to all products
 *
 * @param integer $fieldId - Field-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_setProductFieldArray',
    function ($fieldId) {
        $Field        = Fields::getField($fieldId);
        $productArray = $Field->toProductArray();

        Fields::setFieldAttributesToProducts($fieldId, [
            'unassigned' => $productArray['unassigned'],
            'ownField'   => $productArray['ownField']
        ]);
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
