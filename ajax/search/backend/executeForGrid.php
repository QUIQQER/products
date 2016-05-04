<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_executeForGrid
 */

/**
 * Get all fields that are available for search for a specific Site
 * Return teh result for grid
 *
 * @param array $searchData
 * @return array - product list
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_executeForGrid',
    function ($searchParams) {


    },
    array('searchParams'),
    'Permission::checkAdminUser'
);
