<?php

/**
 * This file contains package_quiqqer_products_ajax_products_getProductTypes
 */

/**
 * Return all product types
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getProductTypes',
    function () {
        $ProductTypes = QUI\ERP\Products\Product\ProductTypes::getInstance();

        $types  = $ProductTypes->getProductTypes();
        $result = [];

        foreach ($types as $type) {
            $title       = call_user_func([$type, 'getTitle']);
            $description = call_user_func([$type, 'getDescription']);

            $result[] = [
                'title'       => $title,
                'description' => $description,
                'class'       => $type
            ];
        }

        return $result;
    }
);
