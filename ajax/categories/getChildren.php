<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getChildren
 */

/**
 * Return the category children
 *
 * @param string $categoryIds - JSON list of category ids
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_categories_getChildren',
    function ($parentId, $flags) {
        $flags = json_decode($flags, true);

        if (empty($flags)) {
            $flags = [];
        }

        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category = $Categories->getCategory((int)$parentId);
        $children = $Category->getChildren();
        $result = [];

        /* @var $Category QUI\ERP\Products\Category\Category */
        foreach ($children as $Category) {
            $attributes = $Category->getAttributes();

            if (!empty($flags['countChildren'])) {
                $attributes['countChildren'] = $Category->countChildren();
            }

            if (!empty($flags['sites'])) {
                $attributes['sites'] = $Category->getSites();
            }

            $result[] = $attributes;
        }

        return $result;
    },
    ['parentId', 'flags'],
    'Permission::checkAdminUser'
);
