<?php

/**
 * This file contains package_quiqqer_products_ajax_products_deactivate
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ExceptionStack;

/**
 * Deactivate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_deactivate',
    function ($productId) {
        if (is_numeric($productId)) {
            $Product = Products::getProduct($productId);
            $Product->deactivate();

            return;
        }

        $ExceptionStack = new ExceptionStack();
        $productIds = json_decode($productId, true);

        if (!$productIds) {
            return;
        }

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getProduct($productId);
                $Product->deactivate();
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            throw $ExceptionStack;
        }
    },
    ['productId'],
    'Permission::checkAdminUser'
);
