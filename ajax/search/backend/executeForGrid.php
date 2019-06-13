<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_executeForGrid
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Utils\Tables;

/**
 * Get all fields that are available for search for a specific Site
 * Return teh result for grid
 *
 * @param array $searchData
 * @return array - product list
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_executeForGrid',
    function ($searchParams) {
        require_once 'execute.php';

        // products
        $result = QUI::$Ajax->callRequestFunction(
            'package_quiqqer_products_ajax_search_backend_execute',
            ['searchParams' => $searchParams]
        );

        $page       = 1;
        $productIds = $result['result'];
        $products   = [];

        // collect product data
        $fields = [
            'active'      => 'active',
            'id'          => 'id',
            'productNo'   => 'productNo',
            'title'       => 'title',
            'description' => 'description',
            'price_netto' => 'F'.Fields::FIELD_PRICE,
            'c_date'      => 'c_date',
            'e_date'      => 'e_date',
            'priority'    => 'F'.Fields::FIELD_PRIORITY
        ];

        if (!empty($productIds)) {
            $result = QUI::getDataBase()->fetch([
                'from'  => Tables::getProductCacheTableName(),
                'where' => [
                    'id'   => [
                        'type'  => 'IN',
                        'value' => $productIds
                    ],
                    'lang' => QUI::getLocale()->getCurrent()
                ]
            ]);
        } else {
            $result = [];
        }

        $currencyCode = QUI\ERP\Currency\Handler::getDefaultCurrency()->getCode();

        foreach ($result as $row) {
            $product = [
                'price_currency' => $currencyCode
            ];

            foreach ($fields as $key => $column) {
                $value = $row[$column];

                switch ($key) {
                    case 'price_netto':
                        $value = (float)$value;
                        break;
                }


                $product[$key] = $value;
            }

            $products[] = $product;
        }

        // count
        $searchParams          = \json_decode($searchParams, true);
        $searchParams['count'] = 1;

        if (isset($searchParams['sheet'])) {
            $page = (int)$searchParams['sheet'];
        }

        $count = QUI::$Ajax->callRequestFunction(
            'package_quiqqer_products_ajax_search_backend_execute',
            ['searchParams' => \json_encode($searchParams)]
        );

        return [
            'data'  => $products,
            'total' => $count['result'],
            'page'  => $page
        ];
    },
    ['searchParams'],
    'Permission::checkAdminUser'
);
