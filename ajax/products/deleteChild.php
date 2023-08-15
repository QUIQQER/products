<?php

/**
 * This file contains package_quiqqer_products_ajax_products_deleteChild
 */

/**
 * Delete a Produkt
 *
 * @param string $productId - Produkt-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_deleteChild',
    function ($productId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Product = $Products->getProduct($productId);

        $Product->delete();
    },
    ['productId'],
    'Permission::checkAdminUser'
);
