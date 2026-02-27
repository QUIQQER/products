<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_suggest
 */

use QUI\ERP\Products\Handler\Search;
use QUI\ERP\Products\Search\FrontendSearch;

/**
 * Get all fields that are available for search for a specific Site
 *
 * @param array $searchData
 * @return array - product ids
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_suggest',
    function ($project, $siteId, $searchParams) {
        QUI\Permissions\Permission::checkPermission(
            Search::PERMISSION_FRONTEND_EXECUTE
        );

        $Project = QUI\Projects\Manager::decode($project);

        try {
            $Site = $Project->get($siteId);
        } catch (QUI\Exception) {
            $Site = $Project->firstChild();
        }

        switch ($Site->getAttribute('type')) {
            case FrontendSearch::SITETYPE_CATEGORY:
            case FrontendSearch::SITETYPE_SEARCH:
                break;

            default:
                $siteList = $Project->getSites([
                    'type' => FrontendSearch::SITETYPE_SEARCH
                ]);

                if (!isset($siteList[0])) {
                    throw new QUI\Exception(
                        [
                            'quiqqer/products',
                            'exception.sitesearch.not.found'
                        ],
                        404
                    );
                }

                $Site = $siteList[0];
        }

        $Search = Search::getFrontendSearch($Site);
        $searchParams = json_decode($searchParams, true);

        $searchParams['limit'] = '0,5';

        if (!isset($searchParams['freetext']) && !isset($searchParams['fields'])) {
            $searchParams['freetext'] = '';
        }

        return $Search->search($searchParams);
    },
    ['project', 'siteId', 'searchParams']
);
