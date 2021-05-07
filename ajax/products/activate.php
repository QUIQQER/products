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
        if (\is_numeric($productId)) {
            $Product = Products::getProduct($productId);
            $Product->activate();

            return;
        }

        $ExceptionStack = new \QUI\ExceptionStack();
        $productIds     = json_decode($productId, true);

        if (!$productIds) {
            return;
        }

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getProduct($productId);
                $Product->activate();
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
