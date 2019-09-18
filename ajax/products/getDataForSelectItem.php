<?php

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Fields;

/**
 * Returns product data needed for a Product SelectItem
 *
 * @param string $productId - Product-ID
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getDataForSelectItem',
    function ($productId) {
        $Product = Products::getProduct($productId);

        return [
            'id'        => $Product->getId(),
            'articleNo' => $Product->getFieldValue(Fields::FIELD_PRODUCT_NO),
            'title'     => $Product->getTitle()
        ];
    },
    ['productId'],
    'Permission::checkAdminUser'
);
