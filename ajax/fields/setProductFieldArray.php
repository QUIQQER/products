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
        $Fields = new Fields();
        $Field  = $Fields->getField($fieldId);

        $productIds   = Products::getProductIds();
        $productArray = $Field->toProductArray();

        foreach ($productIds as $productId) {
            $Product = Products::getProduct($productId);

            if (!$Product->hasField($fieldId)) {
                continue;
            }

            $ProductField = $Product->getField($fieldId);

            $ProductField->setUnassignedStatus($productArray['unassigned']);
            $ProductField->setOwnFieldStatus($productArray['ownField']);
            $ProductField->setPublicStatus($productArray['isPublic']);
            $Product->save();
        }
    },
    array('fieldId'),
    'Permission::checkAdminUser'
);
