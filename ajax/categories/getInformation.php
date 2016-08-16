<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getInformation
 */

/**
 * Return all information about the category
 *
 * @param string $categoryIds - JSON list of categorie ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_getInformation',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryId);

        return array(
            'products'   => $Category->countProducts(),
            'categories' => $Category->countChildren(),
            'fields'     => count($Category->getFields())
        );
    },
    array('categoryId'),
    'Permission::checkAdminUser'
);
