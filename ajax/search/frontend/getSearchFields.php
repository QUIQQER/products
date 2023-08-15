<?php

use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\Utils\Security\Orthos;

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
    function ($siteId, $project, $options) {
        if (empty($options)) {
            $options = [];
        } else {
            $options = Orthos::clearArray(\json_decode($options, true));
        }

        $Project = QUI::getProjectManager()->decode($project);
        $Site = $Project->get($siteId);
        $FrontEndSearch = SearchHandler::getFrontendSearch($Site);

        return $FrontEndSearch->getSearchFields($options);
    },
    ['siteId', 'project', 'options']
);
