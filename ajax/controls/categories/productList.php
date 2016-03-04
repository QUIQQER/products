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
    function ($categoryId, $view, $sort, $row) {
        Categories::getCategory($categoryId);

        $Control = new ProductList(array(
            'categoryId' => $categoryId,
            'view' => $view
        ));

        return $Control->getRow($row);
    },
    array('categoryId', 'view', 'sort', 'row'),
    false
);
