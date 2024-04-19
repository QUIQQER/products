<?php

/**
 * Get settings for the product variant frontend control.
 *
 * package/quiqqer/products/bin/controls/frontend/products/ProductVariant
 *
 * @param string $productId - ID of a product
 * @return string
 */

use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUI\ERP\Products\Handler\Products;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVariantControlSettings',
    function ($productId) {
        try {
            $Product = Products::getProduct($productId);
            $Control = new ProductControl([
                'Product' => $Product
            ]);

            return $Control->getVariantControlSettings();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return [];
        }
    },
    ['productId']
);
