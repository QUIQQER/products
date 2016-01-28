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
    function ($categoryIds) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryIds);

        $Category->delete();
    },
    array('categoryIds'),
    'Permission::checkAdminUser'
);
