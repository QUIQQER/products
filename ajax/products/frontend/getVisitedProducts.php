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
        $productIds = \json_decode($productIds, true);
        $Products   = new QUI\ERP\Products\Handler\Products();
        $Control    = new QUI\ERP\Products\Controls\Products\VisitedProducts();

        if (!isset($currentProductId)) {
            $currentProductId = 0;
        }

        foreach ($productIds as $productId) {
            if (empty($productId) || !\is_numeric($productId)) {
                continue;
            }

            if ($currentProductId == $productId) {
                continue;
            }

            try {
                $Product = $Products->getProduct($productId);
                $View    = $Product->getViewFrontend();

                // check if prices exists
                if (!$Product->isActive()) {
                    continue;
                }

                // check prices if exists
                $View->getMaximumPrice();
                $View->getMinimumPrice();

                $Control->addProduct($View);
            } catch (QUI\Exception $Exception) {
            }
        }

        return $Control->create();
    },
    ['productIds', 'currentProductId']
);
