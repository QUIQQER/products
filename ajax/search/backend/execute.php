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
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_EXECUTE
        );

        $BackEndSearch = SearchHandler::getBackendSearch();
        $searchParams  = \json_decode($searchParams, true);

        if (!empty($searchParams['considerVariantChildren'])) {
            $BackEndSearch->considerVariantChildren();
        }

        if (isset($searchParams['count'])) {
            return $BackEndSearch->search($searchParams, true);
        }

        return $BackEndSearch->search($searchParams);
    },
    ['searchParams'],
    'Permission::checkAdminUser'
);
