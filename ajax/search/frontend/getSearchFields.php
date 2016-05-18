<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_getSearchFields
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param integer $siteId
 * @param string $project
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_getSearchFields',
    function ($siteId, $project) {
        $Project        = QUI::getProjectManager()->decode($project);
        $Site           = $Project->get($siteId);
        $FrontEndSearch = SearchHandler::getFrontendSearch($Site);

        return $FrontEndSearch->getSearchFields();
    },
    array('siteId', 'project')
);
