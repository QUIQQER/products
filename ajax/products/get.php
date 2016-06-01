<?php

/**
 * This file contains package_quiqqer_products_ajax_products_get
 */

/**
 * Returns a product
 *
 * @param string $productId - Product-ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_get',
    function ($productId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Product  = $Products->getProduct($productId);
        
        return $Product->getAttributes();
    },
    array('productId')
);
