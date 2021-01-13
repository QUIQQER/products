<?php

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Fields;

/**
 * Update product prices based on price field multipliers
 *
 * @return void
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_updatePrices',
    function ($activeOnly) {
        $where = [];

        if (!empty($activeOnly)) {
            $where['active'] = 1;
        }

        $productIds = Products::getProductIds([
            'where' => $where
        ]);

        $updateCount = 0;

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getProduct($productId);
                Fields::updateProductPricesByFactors($Product);

                $updateCount++;
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/products',
                'message.ajax.settings.updatePrices.success',
                [
                    'count' => $updateCount
                ]
            )
        );
    },
    ['activeOnly'],
    'Permission::checkAdminUser'
);
