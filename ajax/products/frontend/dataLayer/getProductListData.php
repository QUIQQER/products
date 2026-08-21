<?php

use QUI\ERP\Products\Utils\DataLayer;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_dataLayer_getProductListData',
    function ($productIds, $startIndex) {
        $productIds = json_decode($productIds, true);

        if (!is_array($productIds)) {
            return [];
        }

        $validProductIds = [];

        foreach ($productIds as $productId) {
            if ((!is_int($productId) && !is_string($productId)) || !ctype_digit((string)$productId)) {
                continue;
            }

            $validProductIds[] = (int)$productId;
        }

        return [
            'items' => DataLayer::parseProductItems(
                $validProductIds,
                QUI::getLocale(),
                max(0, (int)$startIndex)
            )
        ];
    },
    ['productIds', 'startIndex']
);
