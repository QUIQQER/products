<?php

/**
 * This file contains package_quiqqer_products_ajax_products_getProductTypes
 */

/**
 * Return all product types
 *
 * @return array<mixed>
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_getProductTypes',
    function () {
        $ProductTypes = QUI\ERP\Products\Utils\ProductTypes::getInstance();

        $types = $ProductTypes->getProductTypes();
        $result = [];

        foreach ($types as $type) {
            if (
                !is_string($type)
                || !is_callable([$type, 'getTypeTitle'])
                || !is_callable([$type, 'getTypeDescription'])
                || !is_callable([$type, 'getTypeBackendPanel'])
                || !is_callable([$type, 'isTypeSelectable'])
            ) {
                continue;
            }

            $title = call_user_func([$type, 'getTypeTitle']);
            $description = call_user_func([$type, 'getTypeDescription']);
            $panel = call_user_func([$type, 'getTypeBackendPanel']);
            $isSelectable = call_user_func([$type, 'isTypeSelectable']);

            $result[] = [
                'type' => $type,
                'typeTitle' => $title,
                'typeDescription' => $description,
                'typeBackendPanel' => $panel,
                'isTypeSelectable' => $isSelectable
            ];
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
