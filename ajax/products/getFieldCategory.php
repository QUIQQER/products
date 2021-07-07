<?php

/**
 * This file contains package_quiqqer_products_ajax_products_getFieldCategory
 */

/**
 * Returns the field list of a field category
 *
 * @param string $category - Name of the category
 * @param int $productId (optional) - Get category fields for specific product
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getFieldCategory',
    function ($category, $productId) {
        $Product = null;

        if (!empty($productId)) {
            try {
                $Product = \QUI\ERP\Products\Handler\Products::getProduct((int)$productId);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return QUI\ERP\Products\Utils\Fields::getPanelFieldCategoryFields($category, $Product);
    },
    ['category', 'productId'],
    'Permission::checkAdminUser'
);
