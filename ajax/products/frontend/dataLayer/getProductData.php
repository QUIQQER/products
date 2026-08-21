<?php

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\DataLayer;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_dataLayer_getProductData',
    function ($productId) {
        try {
            $Product = Products::getProduct((int)$productId);

            return DataLayer::parseProductEvent($Product, QUI::getLocale());
        } catch (QUI\Exception) {
            return [];
        }
    },
    ['productId']
);
