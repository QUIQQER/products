<?php

/**
 * This file contains package_quiqqer_products_ajax_products_update
 */

/**
 * Update a product
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_checkUrl',
    function ($urls, $category) {
        try {
            QUI\ERP\Products\Utils\Products::checkUrlByUrlFieldValue(
                json_decode($urls, true),
                $category
            );
        } catch (QUI\Exception $Exception) {
            return [
                'exists' => true,
                'message' => $Exception->getMessage()
            ];
        }

        return [
            'exists' => false,
            'message' => ''
        ];
    },
    ['urls', 'category'],
    'Permission::checkAdminUser'
);
