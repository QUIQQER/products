<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_getSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_search_backend_getSearchFieldData',
    function () {
        $BackEndSearch = SearchHandler::getBackendSearch();

        return $BackEndSearch->getSearchFieldData();
    },
    [],
    'Permission::checkAdminUser'
);
