<?php

/**
 * This file contains package_quiqqer_products_ajax_search_frontend_suggestRendered
 */

use QUI\ERP\Products\Handler\Search;
use QUI\ERP\Products\Search\FrontendSearch;
use QUI\ERP\Products\Handler\Products;

/**
 * Get all fields that are available for search for a specific Site
 * and returned it as html list
 *
 * @param array $searchData
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_frontend_suggestRendered',
    function ($project, $siteId, $searchParams, $globalsearch) {
        QUI\Permissions\Permission::checkPermission(
            Search::PERMISSION_FRONTEND_EXECUTE
        );

        $limit = 5;

        if (!isset($globalsearch)) {
            $globalsearch = false;
        }

        $Project = QUI\Projects\Manager::decode($project);

        // global search
        // @todo richtige globale suche umsetzen, ist nur ein workaround
        if ($globalsearch) {
            $siteList = $Project->getSites([
                'where' => [
                    'type' => FrontendSearch::SITETYPE_SEARCH
                ],
                'limit' => 1
            ]);

            if (!isset($siteList[0])) {
                throw new QUI\Exception(
                    ['quiqqer/products', 'exception.sitesearch.not.found'],
                    404
                );
            }

            $Site = $siteList[0];
        } else {
            try {
                $Site = $Project->get($siteId);
            } catch (QUI\Exception $Exception) {
                $Site = $Project->firstChild();
            }

            switch ($Site->getAttribute('type')) {
                case FrontendSearch::SITETYPE_CATEGORY:
                case FrontendSearch::SITETYPE_SEARCH:
                    break;

                default:
                    $siteList = $Project->getSites([
                        'where' => [
                            'type' => FrontendSearch::SITETYPE_SEARCH
                        ],
                        'limit' => 1
                    ]);

                    if (!isset($siteList[0])) {
                        throw new QUI\Exception(
                            ['quiqqer/products', 'exception.sitesearch.not.found'],
                            404
                        );
                    }

                    $Site = $siteList[0];
            }
        }

        $Search       = Search::getFrontendSearch($Site);
        $searchParams = \json_decode($searchParams, true);
        $active       = 1;

        if (isset($searchParams['page'])) {
            $active = (int)$searchParams['page'];

            if ($active < 1) {
                $active = 1;
            }
        }

        $searchParams['limitOffset'] = (($active - 1) * $limit);
        $searchParams['limit']       = $limit;

        if (!isset($searchParams['freetext']) && !isset($searchParams['fields'])) {
            $searchParams['freetext'] = '';
        }

        $html   = '';
        $result = $Search->search($searchParams);
        $count  = $Search->search($searchParams, true);

        if (!\count($result)) {
            return $html;
        }

        $pages = \ceil($count / $limit);

        $User = QUI::getUserBySession();

        try {
            $Engine = QUI::getTemplateManager()->getEngine();

            $Engine->assign([
                'result' => $result,
                'Locale' => $User->getLocale(),
                'pages'  => $pages,
                'active' => $active
            ]);

            return $Engine->fetch(OPT_DIR.'quiqqer/products/template/search/frontend/SuggestRendered.html');
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return '';
        }
    },
    ['project', 'siteId', 'searchParams', 'globalsearch']
);
