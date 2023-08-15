<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_setSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Set product search fields, which fields are shown at the product search
 *
 * @param array $searchFields
 * @return array - searchfields after set
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_setProductSearchFields',
    function ($searchFields) {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_CONFIGURE
        );

        $BackEndSearch = SearchHandler::getBackendSearch();
        $searchFields = QUI\Utils\Security\Orthos::clearArray(
            json_decode($searchFields, true)
        );

        return $BackEndSearch->setProductSearchFields($searchFields);
    },
    ['searchFields'],
    'Permission::checkAdminUser'
);
