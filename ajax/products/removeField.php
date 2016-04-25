<?php

/**
 * This file contains package_quiqqer_products_ajax_products_removeField
 */

/**
 * Remove a field from the product
 *
 * @param integer $productId - product-ID
 * @param integer $fieldId - Field-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_removeField',
    function ($productId, $fieldId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Product  = $Products->getProduct($productId);
        $Field    = $Product->getField($fieldId);

        $Product->removeField($Field);
        $Product->save();
    },
    array('productId', 'fieldId'),
    'Permission::checkAdminUser'
);
