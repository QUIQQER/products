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
    'package_quiqqer_products_ajax_products_getChildren',
    function ($productIds) {
        $productIds = json_decode($productIds, true);
        $Products   = new QUI\ERP\Products\Handler\Products();
        $result     = array();

        foreach ($productIds as $productId) {
            $Product  = $Products->getProduct($productId);
            $result[] = $Product->getAttributes();
        }

        return $result;
    },
    array('productIds')
);
