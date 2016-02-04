<?php

/**
 * This file contains package_quiqqer_products_ajax_products_list
 */

/**
 * Returns product list for a grid
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_list',
    function ($params) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $result   = array();

        $Grid = new QUI\Utils\Grid();

        $data = $Products->getProducts(
            $Grid->parseDBParams(json_decode($params, true))
        );

        /* @var $Product \QUI\ERP\Products\Product\Product */
        foreach ($data as $Product) {
            $result[] = $Product->getAttributes();
        }

        usort($result, function ($a, $b) {
            return $a['title'] > $b['title'];
        });

        return $Grid->parseResult($result, $Products->countProducts());
    },
    array('params'),
    'Permission::checkAdminUser'
);
