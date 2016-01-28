<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_get
 */

/**
 * Returns a category
 *
 * @param string $id - Category-ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_get',
    function ($id) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($id);
        $attributes = $Category->getAttributes();

        $attributes['title'] = $Category->getTitle();

        return $attributes;
    },
    array('id'),
    'Permission::checkAdminUser'
);
