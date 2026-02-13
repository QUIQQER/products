<?php

/**
 * Return the html for a product list
 *
 * @param string $project - JSON project params
 *
 * @return string
 */

use QUI\ERP\Products\Controls\ManufacturerList\ManufacturerList;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_controls_manufacturerList_get',
    function ($project, $siteId, $view, $searchParams, $next, $articles) {
        $Project = QUI\Projects\Manager::decode($project);
        $Site = $Project->get($siteId);

        $Control = new ManufacturerList([
            'view' => $view,
            'Site' => $Site,
            'searchParams' => json_decode($searchParams, true)
        ]);

        if ($next) {
            return $Control->getNext($articles);
        }

        return $Control->getStart();
    },
    ['project', 'siteId', 'view', 'searchParams', 'next', 'articles']
);
