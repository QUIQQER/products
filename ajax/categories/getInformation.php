<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getInformation
 */

/**
 * Return all information about the category
 *
 * @param string $categoryIds - JSON list of categories ids
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_categories_getInformation',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category = $Categories->getCategory($categoryId);

        return [
            'products' => $Category->countProducts(),
            'categories' => $Category->countChildren(),
            'fields' => count($Category->getFields())
        ];
    },
    ['categoryId'],
    'Permission::checkAdminUser'
);
