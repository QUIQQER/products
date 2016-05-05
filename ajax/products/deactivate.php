<?php

/**
 * This file contains package_quiqqer_products_ajax_products_deactivate
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Deactivate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_deactivate',
    function ($productId) {
        $Product = Products::getProduct($productId);
        $Product->deactivate();
    },
    array('productId')
);
