<?php

/**
 * This file contains package_quiqqer_products_ajax_products_setPermissions
 */

/**
 * Set the permissions from a product
 *
 * @param integer $productId - product-ID
 * @param integer $permissions - JSON permissions string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_setPermissions',
    function ($productId, $permissions) {
        $Products    = new QUI\ERP\Products\Handler\Products();
        $Product     = $Products->getProduct($productId);
        $permissions = \json_decode($permissions, true);

        $Product->clearPermissions();
        $Product->setPermissions($permissions);
        $Product->save();
    },
    ['productId', 'permissions'],
    'Permission::checkAdminUser'
);
