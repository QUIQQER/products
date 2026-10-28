<?php

/**
 * This file contains package_quiqqer_products_ajax_products_activate
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\ProductActivation;
use QUI\ExceptionStack;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 * @param integer $activateImages - Set to 1 to also activate inactive product images
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_activate',
    function ($productId, $activateImages = 0) {
        if (is_numeric($productId)) {
            $Product = Products::getProduct((int)$productId);
            ProductActivation::activate($Product, (int)$activateImages === 1);

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
                ProductActivation::activate($Product, (int)$activateImages === 1);
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            throw $ExceptionStack;
        }
    },
    ['productId', 'activateImages'],
    'Permission::checkAdminUser'
);
