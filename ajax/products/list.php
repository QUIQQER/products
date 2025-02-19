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

use QUI\ERP\Products\Product\Product;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_list',
    function ($params) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $result = [];

        $Grid = new QUI\Utils\Grid();

        $data = $Products->getProducts(
            $Grid->parseDBParams(json_decode($params, true))
        );

        /* @var $Product Product */
        foreach ($data as $Product) {
            $attributes = $Product->getAttributes();

            try {
                $attributes['title'] = $Product->getTitle();
            } catch (QUI\Exception) {
            }

            try {
                $attributes['description'] = $Product->getDescription();
            } catch (QUI\Exception) {
            }

            try {
                $attributes['price'] = $Product->getPrice()->value();
            } catch (QUI\Exception) {
            }

            $result[] = $attributes;
        }

        usort($result, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        return $Grid->parseResult($result, $Products->countProducts());
    },
    ['params'],
    'Permission::checkAdminUser'
);
