<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_list
 */

/**
 * Returns category list for a grid
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_list',
    function ($params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $result     = array();

        $Grid = new \QUI\Utils\Grid();

        $data = $Categories->getCategories(
            $Grid->parseDBParams(json_decode($params, true))
        );

        /* @var $Category \QUI\ERP\Products\Category\Category */
        foreach ($data as $Category) {
            $entry          = $Category->getAttributes();
            $entry['title'] = $Category->getTitle();

            $result[] = $entry;
        }

        usort($result, function ($a, $b) {
            return $a['title'] > $b['title'];
        });

        return $Grid->parseResult($result, $Categories->countCategories());
    },
    array('params'),
    'Permission::checkAdminUser'
);
