<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_getProductSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for the product search at the backend
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_search_backend_getProductSearchFields',
    function () {
        $BackEndSearch = SearchHandler::getBackendSearch();

        return $BackEndSearch->getProductSearchFields();
    },
    [],
    'Permission::checkAdminUser'
);
