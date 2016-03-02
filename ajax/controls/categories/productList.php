<?php

/**
 * This file contains package_quiqqer_products_ajax_controls_categories_productList
 */

/**
 * Return the html for a prduct list
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_controls_categories_productList',
    function ($categoryId, $view, $sort, $page) {

    },
    array('categoryId', 'view', 'sort', 'page'),
    false
);
