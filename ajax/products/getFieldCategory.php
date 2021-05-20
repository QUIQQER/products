<?php

/**
 * This file contains package_quiqqer_products_ajax_products_getFieldCategory
 */

/**
 * Returns the field list of a field category
 *
 * @param string $category - Name of the category
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getFieldCategory',
    function ($category) {
        return QUI\ERP\Products\Utils\Fields::getPanelFieldCategoryFields($category);
    },
    ['category'],
    'Permission::checkAdminUser'
);
