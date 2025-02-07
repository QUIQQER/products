<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProductControlClass
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Return the product control class
 *
 * @param string $productId - ID of a product
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getProductControlClass',
    function ($productId) {
        $Product = Products::getProduct((int)$productId);

        if (
            $Product instanceof VariantParent ||
            $Product instanceof VariantChild
        ) {
            return 'package/quiqqer/products/bin/controls/frontend/products/ProductVariant';
        }

        return 'package/quiqqer/products/bin/controls/frontend/products/Product';
    },
    ['productId']
);
