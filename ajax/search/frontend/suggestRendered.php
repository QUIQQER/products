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

        $searchParams['limit'] = '0,5';

        if (!isset($searchParams['freetext']) && !isset($searchParams['fields'])) {
            $searchParams['freetext'] = '';
        }

        $html   = '';
        $result = $Search->search($searchParams);

        if (!\count($result)) {
            return $html;
        }

        $User   = QUI::getUserBySession();
        $Locale = $User->getLocale();
        $html   = '<ul>';

        // @todo template daraus bauen
        foreach ($result as $productId) {
            try {
                $Product   = Products::getProduct($productId);
                $Image     = $Product->getImage();
                $ArticleNo = $Product->getField(\QUI\ERP\Products\Handler\Fields::FIELD_PRODUCT_NO);
                $articleNo = $ArticleNo->getValueByLocale($Locale);
                $url       = $Product->getUrl();

                $html .= '<li data-url="'.$url.'">';

                $html .= '<div class="quiqqer-products-search-suggest-dropdown-icon">';
                $html .= '<img src="'.$Image->getSizeCacheUrl(100, 100).'" />';
                $html .= '</div>';

                $html .= '<div class="quiqqer-products-search-suggest-dropdown-text">';
                $html .= '<div class="quiqqer-products-search-suggest-dropdown-title">';
                $html .= $Product->getTitle($Locale);
                $html .= '</div>';

                $html .= '<div class="quiqqer-products-search-suggest-dropdown-description">';
                $html .= $Product->getDescription($Locale);

                if (!empty($articleNo)) {
                    $html .= '<div class="quiqqer-products-search-suggest-dropdown-description-articlNo">';
                    $html .= '<span>';
                    $html .= $Locale->get('quiqqer/products', 'productNo');
                    $html .= ':</span>';
                    $html .= $ArticleNo->getValueByLocale($Locale);
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';

                $html .= '</li>';
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
            }
        }

        $html .= '</ul>';

        return $html;
    },
    ['project', 'siteId', 'searchParams', 'globalsearch']
);
