<?php

/**
 * This file contains package_quiqqer_products_ajax_controls_search_suggestTemplate
 */

/**
 * Return the html for the suggest search
 *
 * @param string $project - JSON project params
 * @param string $siteId - Site-ID
 *
 * @return string
 */

use QUI\ERP\Products\Controls\Search\Suggest;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_controls_search_suggestTemplate',
    function ($project, $siteId) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site = $Project->get($siteId);

        $Control = new Suggest([
            'Site' => $Site,
            'Project' => $Project
        ]);

        $result = QUI\Control\Manager::getCSS();
        $result .= $Control->getBody();

        return $result;
    },
    ['project', 'siteId']
);
