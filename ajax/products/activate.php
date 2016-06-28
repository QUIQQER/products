<?php

/**
 * This file contains package_quiqqer_products_ajax_products_activate
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_activate',
    function ($productId) {
        $Product = Products::getProduct($productId);
        $Product->activate();
    },
    array('productId'),
    'Permission::checkAdminUser'
);
