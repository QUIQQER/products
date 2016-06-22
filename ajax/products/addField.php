<?php

/**
 * This file contains package_quiqqer_products_ajax_products_addField
 */

/**
 * Add a field to a product
 * - change the field to an own field
 *
 * @param integer $productId - product-ID
 * @param integer $fieldId - Field-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_addField',
    function ($productId, $fieldId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Fields   = new QUI\ERP\Products\Handler\Fields();

        $Product = $Products->getProduct($productId);
        $Field   = $Fields->getField($fieldId);
            
        $Product->addOwnField($Field);
        $Product->save();
    },
    array('productId', 'fieldId'),
    'Permission::checkAdminUser'
);
