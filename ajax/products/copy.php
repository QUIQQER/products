<?php

/**
 * This file contains package_quiqqer_products_ajax_products_copy
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Create a new product
 *
 * @param string|integer $productId - Product ID
 * @return integer - new product id
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_copy',
    function ($productId) {
        return Products::copyProduct($productId)->getId();
    },
    ['productId'],
    'Permission::checkAdminUser'
);
