<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getFields
 */

/**
 * Return all fields from the categories
 *
 * @param string $categoryIds - JSON list of categorie ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_getFields',
    function ($categoryIds) {
        $Categories  = new QUI\ERP\Products\Handler\Categories();
        $categoryIds = \json_decode($categoryIds);

        $children = [];
        $fields   = [];
        $result   = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $children[] = $Categories->getCategory((int)$categoryId);
            } catch (QUI\Exception $Exception) {
            }
        }

        /* @var $Category QUI\ERP\Products\Category\Category */
        foreach ($children as $Category) {
            $fields = \array_merge($fields, $Category->getFields());
        }

        // cleanup
        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if (!isset($results[$Field->getId()])) {
                $result[] = $Field->getAttributes();
            }
        }

        return $result;
    },
    ['categoryIds'],
    'Permission::checkAdminUser'
);
