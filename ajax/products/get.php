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
    'package_quiqqer_products_ajax_products_get',
    function ($productId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Product  = $Products->getProduct($productId);

        if (QUI::isFrontend()) {
            return $Product->getView()->getAttributes();
        }

        $attributes = $Product->getAttributes();

        foreach ($attributes['fields'] as $key => $field) {
            $attributes['fields'][$key]['source'] = $Product->getFieldSource($field['id']);
        }

        return $attributes;
    },
    array('productId')
);
