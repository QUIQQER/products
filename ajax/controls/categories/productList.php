<?php

/**
 * This file contains package_quiqqer_products_ajax_controls_categories_productList
 */

use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Controls\Category\ProductList;

/**
 * Return the html for a prduct list
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_controls_categories_productList',
    function ($project, $siteId, $categoryId, $view, $row, $searchParams) {
        Categories::getCategory($categoryId);

        $Project = QUI\Projects\Manager::decode($project);
        $Site    = $Project->get($siteId);

        $Control = new ProductList(array(
            'categoryId' => $categoryId,
            'view' => $view,
            'Site' => $Site,
            'searchParams' => json_decode($searchParams, true)
        ));

        return $Control->getRow($row);
    },
    array('project', 'siteId', 'categoryId', 'view', 'row', 'searchParams'),
    false
);
