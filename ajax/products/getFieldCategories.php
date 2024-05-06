<?php

/**
 * Returns the field categories
 *
 * @param int $productId (optional) - Get field categories for specific product
 *
 * @return array
 */

use QUI\ERP\Products\Handler\Products;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getFieldCategories',
    function ($productId) {
        $Product = null;

        if (!empty($productId)) {
            try {
                $Product = Products::getProduct((int)$productId);
            } catch (Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return QUI\ERP\Products\Utils\Fields::getPanelFieldCategories($Product);
    },
    ['productId'],
    'Permission::checkAdminUser'
);
