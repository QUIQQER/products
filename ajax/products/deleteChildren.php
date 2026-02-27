<?php

/**
 * This file contains package_quiqqer_products_ajax_products_deleteChildren
 */

/**
 * Delete products
 *
 * @param string $productIds - JSON list of product ids
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_deleteChildren',
    function ($productIds) {
        $productIds = json_decode($productIds, true);
        $Products = new QUI\ERP\Products\Handler\Products();

        foreach ($productIds as $productId) {
            try {
                $Product = $Products->getProduct($productId);
                $Product->delete();
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addAttention($Exception->getMessage());
            }
        }
    },
    ['productIds'],
    'Permission::checkAdminUser'
);
