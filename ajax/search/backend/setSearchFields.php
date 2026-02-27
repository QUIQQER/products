<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_setSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param array $searchFields
 * @return array - search fields after set
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_search_backend_setSearchFields',
    function ($searchFields) {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_CONFIGURE
        );

        $BackEndSearch = SearchHandler::getBackendSearch();
        $searchFields = QUI\Utils\Security\Orthos::clearArray(
            json_decode($searchFields, true)
        );

        return $BackEndSearch->setSearchFields($searchFields);
    },
    ['searchFields'],
    'Permission::checkAdminUser'
);
