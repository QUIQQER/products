<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_execute
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param array $searchData
 * @return array - product ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_execute',
    function ($searchParams) {
        QUI\Rights\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_EXECUTE
        );

        $BackEndSearch = SearchHandler::getBackendSearch();
        $searchParams  = QUI\Utils\Security\Orthos::clearArray(json_decode($searchParams, true));

        if (isset($searchParams['count'])) {
            return $BackEndSearch->search($searchParams, true);
        }

        return $BackEndSearch->search($searchParams);
    },
    array('searchParams'),
    'Permission::checkAdminUser'
);
