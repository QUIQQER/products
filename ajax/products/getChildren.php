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
        $productIds = \json_decode($productIds, true);
        $Products   = new QUI\ERP\Products\Handler\Products();
        $result     = [];

        $ExStack = new QUI\ExceptionStack();

        foreach ($productIds as $productId) {
            try {
                $Product  = $Products->getProduct($productId);
                $result[] = $Product->getAttributes();
            } catch (QUI\Exception $Exception) {
                $ExStack->addException($Exception);
            }
        }

        if (!$ExStack->isEmpty()) {
            throw new QUI\Exception($ExStack->getMessage());
        }

        return $result;
    },
    ['productIds']
);
