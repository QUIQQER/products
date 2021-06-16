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
        /*
         * unassigned and ownField attributes are not explicit attributes
         * of the field itself but of the product in conjunction with a field.
         *
         * thus it must not be set here.
         */
//        $Field        = Fields::getField($fieldId);
//        $productArray = $Field->toProductArray();

        Fields::setFieldAttributesToProducts((int)$fieldId, [
//            'unassigned' => $productArray['unassigned'],
//            'ownField'   => $productArray['ownField']
        ]);
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
