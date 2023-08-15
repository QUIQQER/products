<?php

/**
 * This file contains package_quiqqer_products_ajax_search_global_setSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Set search settings for the global freetext search
 *
 * @param array $searchFields
 * @return array - searchfields after set
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_global_setSearchFields',
    function ($searchFields) {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_CONFIGURE
        );

        $GlobaleSearch = new QUI\ERP\Products\Search\GlobalFrontendSearch();
        $searchFields = QUI\Utils\Security\Orthos::clearArray(
            \json_decode($searchFields, true)
        );

        return $GlobaleSearch->setSearchFields($searchFields);
    },
    ['searchFields'],
    'Permission::checkAdminUser'
);
