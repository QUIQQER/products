<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_getSearchFields
 */

use \QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_getSearchFields',
    function () {
        $BackEndSearch = SearchHandler::getBackendSearch();

        return $BackEndSearch->getSearchFields();
    },
    [],
    'Permission::checkAdminUser'
);
