<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_deleteChildren
 */

/**
 * Delete categories
 *
 * @param string $categoryIds - JSON list of categorie ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_deleteChildren',
    function ($categoryIds) {
        $categoryIds = \json_decode($categoryIds, true);
        $Categories = new QUI\ERP\Products\Handler\Categories();

        foreach ($categoryIds as $categoryId) {
            try {
                $Category = $Categories->getCategory($categoryId);
                $Category->delete();
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addAttention($Exception->getMessage());
            }
        }
    },
    ['categoryIds'],
    'Permission::checkAdminUser'
);
