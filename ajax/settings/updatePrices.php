<?php

/**
 * Update product prices based on price field multipliers
 *
 * @return void
 */

use QUI\ERP\Products\Console\UpdatePrices;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_settings_updatePrices',
    function ($activeOnly, $categoryId = null) {
        $UpdateTool = new UpdatePrices();
        $updateCount = $UpdateTool->updateProductPrices(!empty($activeOnly), $categoryId ? (int)$categoryId : null);

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
    ['activeOnly', 'categoryId'],
    ['Permission::checkAdminUser', 'product.edit']
);
