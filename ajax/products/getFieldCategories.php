<?php

/**
 * This file contains package_quiqqer_products_ajax_products_getFieldCategories
 */

/**
 * Returns the field categories
 *
 * @param string $productId - Product-ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getFieldCategories',
    function () {
        return QUI\ERP\Products\Utils\Fields::getPanelFieldCategories();
    },
    false,
    'Permission::checkAdminUser'
);
