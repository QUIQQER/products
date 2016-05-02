<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_setSearchFields
 */

use \QUI\ERP\Products\Handler\Search as SearchHandler;

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
    'package_quiqqer_products_ajax_search_backend_setSearchFields',
    function ($searchFields) {
        $BackEndSearch = SearchHandler::getBackendSearch();
        $searchFields  = \QUI\Utils\Security\Orthos::clearArray(
            json_decode($searchFields, true)
        );

        return $BackEndSearch->setSearchFields($searchFields);
    },
    array('searchFields'),
    'Permission::checkAdminUser'
);
