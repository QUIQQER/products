<?php

/**
 * This file contains package_quiqqer_products_ajax_controls_categories_productList
 */

use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Handler\Categories;

/**
 * Return the html for a prduct list
 *
 * @param string $project - JSON project params
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_controls_categories_productList',
    function ($project, $siteId, $categoryId, $productLoadNumber, $view, $searchParams, $next, $articles) {
        try {
            Categories::getCategory($categoryId);
        } catch (QUI\Exception $Exception) {
            $categoryId = false;
        }

        $Project = QUI\Projects\Manager::decode($project);
        $Site = $Project->get($siteId);

        $Control = new ProductList([
            'categoryId' => $categoryId,
            'view' => $view,
            'Site' => $Site,
            'searchParams' => \json_decode($searchParams, true),
            'productLoadNumber' => $productLoadNumber
        ]);

        if ($next) {
            return $Control->getNext($articles);
        }

        return $Control->getStart();
    },
    ['project', 'siteId', 'categoryId', 'productLoadNumber', 'view', 'searchParams', 'next', 'articles'],
    false
);
