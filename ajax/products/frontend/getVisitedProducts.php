<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_setCustomFieldValues
 */

/**
 * Get the fields for a frontend product
 *
 * @param integer $productId - Product-ID
 * @param array $fields
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVisitedProducts',
    function ($productIds, $currentProductId) {
        $productIds = json_decode($productIds, true);
        $Products   = new QUI\ERP\Products\Handler\Products();
        $Control    = new QUI\ERP\Products\Controls\Products\VisitedProducts();

        if (!isset($currentProductId)) {
            $currentProductId = 0;
        }

        foreach ($productIds as $productId) {
            if ($currentProductId == $productId) {
                continue;
            }

            try {
                $Product = $Products->getProduct($productId);
                $Control->addProduct($Product->getViewFrontend());
            } catch (QUI\Exception $Exception) {
            }
        }

        return $Control->create();
    },
    ['productIds', 'currentProductId']
);
