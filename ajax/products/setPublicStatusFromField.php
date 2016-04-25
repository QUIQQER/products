<?php

/**
 * This file contains package_quiqqer_products_ajax_products_setPublicStatusFromField
 */

/**
 * Set the public status from a product field
 *
 * @param integer $productId - product-ID
 * @param integer $fieldId - Field-ID
 * @return boolean
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_setPublicStatusFromField',
    function ($productId, $fieldId, $status) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Product  = $Products->getProduct($productId);
        $Field    = $Product->getField($fieldId);

        $Field->setPublicStatus((bool)$status);
        $Product->save();

        return $Product->getField($fieldId)->isPublic();
    },
    array('productId', 'fieldId', 'status'),
    'Permission::checkAdminUser'
);
