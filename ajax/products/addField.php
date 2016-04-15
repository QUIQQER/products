<?php

/**
 * This file contains package_quiqqer_products_ajax_products_addField
 */

/**
 * Add a field to a product
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_addField',
    function ($productId, $fieldId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Fields   = new QUI\ERP\Products\Handler\Fields();

        $Product = $Products->getProduct($productId);
        $Field   = $Fields->getField($fieldId);

        $Field->setUnassignedStatus(false);

        $Product->addField($Field);
        $Product->save();
    },
    array('productId', 'fieldId'),
    'Permission::checkAdminUser'
);
