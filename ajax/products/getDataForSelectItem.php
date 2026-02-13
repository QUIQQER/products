<?php

/**
 * Returns product data needed for a Product SelectItem
 *
 * @param string $productId - Product-ID
 * @return array
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_getDataForSelectItem',
    function ($productId) {
        $Product = Products::getProduct($productId);

        return [
            'id' => $Product->getId(),
            'gtin' => $Product->getFieldValue(Fields::FIELD_EAN),
            'articleNo' => $Product->getFieldValue(Fields::FIELD_PRODUCT_NO),
            'title' => $Product->getTitle()
        ];
    },
    ['productId'],
    'Permission::checkAdminUser'
);
