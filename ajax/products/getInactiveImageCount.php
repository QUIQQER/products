<?php

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\ProductActivation;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_getInactiveImageCount',
    function ($productIds): int {
        QUI\Permissions\Permission::checkPermission('product.activate');
        $productIds = json_decode($productIds, true);

        if (!is_array($productIds)) {
            return 0;
        }

        $images = [];

        foreach ($productIds as $productId) {
            $Product = Products::getProduct((int)$productId);
            $images += ProductActivation::getInactiveImages($Product);
        }

        return count($images);
    },
    ['productIds'],
    'Permission::checkAdminUser'
);
