<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_get
 */

/**
 * Returns a category
 *
 * @param string $categoryId - Category-ID
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_categories_get',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category = $Categories->getCategory($categoryId);

        return $Category->getAttributes();
    },
    ['categoryId'],
    'Permission::checkAdminUser'
);
