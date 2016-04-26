<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_setParent
 */

/**
 * Set the parent to a category
 *
 * @param string|integer $categoryId - Category ID
 * @param string|integer $parentId - Parent ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_setParent',
    function ($categoryId, $parentId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryId);

        $Category->setParentId((int)$parentId);
        $Category->save();
    },
    array('categoryId', 'parentId'),
    'Permission::checkAdminUser'
);
