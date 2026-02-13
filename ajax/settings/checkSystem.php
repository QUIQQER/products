<?php

/**
 * Checks if the system is capable of updating all product prices via web server request.
 *
 * @return array
 */

use QUI\ERP\Products\Handler\Products;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_settings_checkSystem',
    function ($categoryId = null) {
        $maxExecTime = ini_get('max_execution_time');
        $where = [];

        if (!empty($categoryId)) {
            $categoryId = (int)$categoryId;

            $where['categories'] = [
                'type' => '%LIKE%',
                'value' => '%,' . $categoryId . ',%'
            ];
        }

        $productIds = Products::getProductIds(['where' => $where]);
        $estExecTime = count($productIds) * 0.2;

        return [
            'commands' => [
                'all' => 'cd ' . CMS_DIR . ' && ./console products:update-prices'
                    . ($categoryId ? ' --categoryId=' . $categoryId : ''),
                'active' => 'cd ' . CMS_DIR . ' && ./console products:update-prices --activeOnly'
                    . ($categoryId ? ' --categoryId=' . $categoryId : '')
            ],
            'timeSufficient' => $estExecTime < $maxExecTime
        ];
    },
    [
        'categoryId'
    ],
    ['Permission::checkAdminUser', 'product.edit']
);
