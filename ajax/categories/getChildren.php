<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getChildren
 */

/**
 * Delete categories
 *
 * @param string $categoryIds - JSON list of categorie ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_getChildren',
    function ($parentId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory((int)$parentId);
        $children   = $Category->getChildren();
        $result     = array();

        /* @var $Category QUI\ERP\Products\Category\Category */
        foreach ($children as $Category) {
            $result[] = $Category->getAttributes();
        }

        return $result;
    },
    array('parentId'),
    'Permission::checkAdminUser'
);
