<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_setGlobalSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param array $searchFields
 * @param integer $siteId
 * @param string $project
 *
 * @return array - searchfields after set
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_setGlobalSearchFields',
    function ($searchFields) {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_FRONTEND_CONFIGURE
        );

        $searchFields = QUI\Utils\Security\Orthos::clearArray(
            json_decode($searchFields, true)
        );

        return QUI\ERP\Products\Search\FrontendSearch::setGlobalSearchFields($searchFields);
    },
    ['searchFields'],
    'Permission::checkAdminUser'
);
