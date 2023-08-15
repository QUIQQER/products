<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_create
 */

/**
 * Create a new  category
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_create',
    function ($parentId, $params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $params = json_decode($params, true);
        $title = '';

        if (isset($params['title'])) {
            $title = $params['title'];
        }

        $Category = $Categories->createCategory($parentId, $title);

        return $Category->getAttributes();
    },
    ['parentId', 'params'],
    'Permission::checkAdminUser'
);
