<?php

use QUI\ERP\Products\Console\UpdatePrices;
use QUI\ERP\Products\Handler\Products;

/**
 * Checks if the system is capable of updating all product prices via web server request.
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_checkSystem',
    function () {
        $maxExecTime = \ini_get('max_execution_time');
        $estExecTime = \count(Products::getProductIds()) * 0.2;

        return [
            'commands'       => [
                'all'    => 'cd '.CMS_DIR.' && ./console products:update-prices',
                'active' => 'cd '.CMS_DIR.' && ./console products:update-prices --activeOnly'
            ],
            'timeSufficient' => $estExecTime < $maxExecTime
        ];
    },
    [],
    ['Permission::checkAdminUser', 'product.edit']
);
