<?php

/**
 * Update product prices based on price field multipliers
 *
 * @return void
 */

use QUI\ERP\Products\Console\UpdatePrices;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_updatePrices',
    function ($activeOnly) {
        $UpdateTool = new UpdatePrices();
        $updateCount = $UpdateTool->updateProductPrices(!empty($activeOnly));

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
    ['Permission::checkAdminUser', 'product.edit']
);
