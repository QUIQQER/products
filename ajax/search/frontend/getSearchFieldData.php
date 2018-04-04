<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_getSearchFieldData
 */

use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get search data for a specific site for frontend search
 *
 * @param integer $siteId
 * @param string $project
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_getSearchFieldData',
    function ($siteId, $project) {
        $Project        = QUI::getProjectManager()->decode($project);
        $Site           = $Project->get($siteId);
        $FrontEndSearch = SearchHandler::getFrontendSearch($Site);

        return $FrontEndSearch->getSearchFieldData();
    },
    ['siteId', 'project']
);
