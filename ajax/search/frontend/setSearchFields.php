<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_create
 */

use \QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param array $searchFields
 * @param integer $siteId
 * @param string $projectName
 * @param string $projectLang
 *
 * @return array - searchfields after set
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_setSearchFields',
    function ($searchFields, $siteId, $projectName, $projectLang) {
        $Project = QUI::getProject($projectName, $projectLang);
        $Site    = $Project->get($siteId);

        $FrontEndSearch = SearchHandler::getFrontendSearch($Site);
        $searchFields   = \QUI\Utils\Security\Orthos::clearArray(
            json_decode($searchFields, true)
        );

        return $FrontEndSearch->setSearchFields($searchFields);
    },
    array('searchFields', 'siteId', 'projectName', 'projectLang'),
    'Permission::checkAdminUser'
);
