<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_setFieldsToAllProducts
 */

/**
 * Update all product fields with the category id fields
 *
 * @param string $categoryId - Category ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_setFieldsToAllProducts',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryId);

        $Category->setFieldsToAllProducts();
    },
    ['categoryId'],
    'Permission::checkAdminUser'
);
