<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_execute
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\Utils\Security\Orthos;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param array $searchData
 * @return array - product ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_execute',
    function ($project, $siteId, $searchParams) {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_FRONTEND_EXECUTE
        );

        $Project = QUI\Projects\Manager::decode($project);
        $Site    = $Project->get($siteId);

        $Search       = SearchHandler::getFrontendSearch($Site);
        $searchParams = Orthos::clearArray(json_decode($searchParams, true));

        if (isset($searchParams['count'])) {
            return $Search->search($searchParams, true);
        }

        return $Search->search($searchParams);
    },
    array('project', 'siteId', 'searchParams')
);
