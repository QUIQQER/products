<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_create
 */

use \QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param integer $siteId
 * @param string $projectName
 * @param string $projectLang
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_getSearchFields',
    function ($siteId, $projectName, $projectLang) {
        $Project        = QUI::getProject($projectName, $projectLang);
        $Site           = $Project->get($siteId);
        $FrontEndSearch = SearchHandler::getFrontendSearch($Site);

        return $FrontEndSearch->getSearchFields();
    },
    array('siteId', 'projectName', 'projectLang'),
    'Permission::checkAdminUser'
);
