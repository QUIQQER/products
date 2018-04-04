<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_deleteChild
 */

/**
 * Delete a category
 *
 * @param string $id - Category-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_deleteChild',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryId);

        $Category->delete();
    },
    ['categoryId'],
    'Permission::checkAdminUser'
);
