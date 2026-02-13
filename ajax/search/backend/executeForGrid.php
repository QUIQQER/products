<?php

/**
 * Get all fields that are available for search for a specific Site
 * Return teh result for grid
 *
 * @param array $searchData
 * @return array - product list
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Utils\Tables;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_search_backend_executeForGrid',
    function ($searchParams) {
        require_once 'execute.php';

        // products
        $result = QUI::getAjax()->callRequestFunction(
            'package_quiqqer_products_ajax_search_backend_execute',
            ['searchParams' => $searchParams]
        );

        if (empty($result) || empty($result['result'])) {
            return [
                'data' => [],
                'total' => 0,
                'page' => 1
            ];
        }

        $page = 1;
        $productIds = $result['result'];
        $products = [];

        $BackEndSearch = SearchHandler::getBackendSearch();
        $productSearchFieldIds = $BackEndSearch->getProductSearchFields();

        // collect product data
        $fields = [
            'active' => 'active',
            'id' => 'id',
            'productNo' => 'productNo',
            'title' => 'title',
            'description' => 'description',
            'type' => 'type',
            'price_netto' => 'F' . Fields::FIELD_PRICE,
            'price_offer' => 'F' . Fields::FIELD_PRICE_OFFER,
            'c_date' => 'c_date',
            'e_date' => 'e_date',
            'priority' => 'F' . Fields::FIELD_PRIORITY
        ];


        foreach ($productSearchFieldIds as $fieldId => $val) {
            if (!$val) {
                continue;
            }

            switch ($fieldId) {
                case 1: // price
                case 3: // product no / article no
                case 4: // title
                case 5: // short
                case 18: // sort
                    break 2;
            }

            $fields['F' . $fieldId] = 'F' . $fieldId;
        }

        if (is_array($productIds) && count($productIds)) {
            $result = QUI::getDataBase()->fetch([
                'from' => Tables::getProductCacheTableName(),
                'where' => [
                    'id' => [
                        'type' => 'IN',
                        'value' => $productIds
                    ],
                    'lang' => QUI::getLocale()->getCurrent()
                ]
            ]);
        } else {
            $result = [];
        }

        $currencyCode = QUI\ERP\Currency\Handler::getDefaultCurrency()->getCode();

        // sort $result as $productIds
        usort($result, function ($rowA, $rowB) use ($productIds) {
            $keyA = array_search($rowA['id'], $productIds);
            $keyB = array_search($rowB['id'], $productIds);

            return $keyA - $keyB;
        });

        foreach ($result as $row) {
            $product = [
                'price_currency' => $currencyCode
            ];

            foreach ($fields as $key => $column) {
                if (!isset($row[$column])) {
                    continue;
                }

                $value = $row[$column];

                switch ($key) {
                    case 'price_netto':
                        $value = (float)$value;
                        break;

                    case 'price_offer':
                        if (empty($value)) {
                            $value = '';
                        } else {
                            $value = (float)$value;
                        }

                        break;
                }


                $product[$key] = $value;
            }

            $products[] = $product;
        }

        // count
        $searchParams = json_decode($searchParams, true);
        $searchParams['count'] = 1;

        if (isset($searchParams['sheet'])) {
            $page = (int)$searchParams['sheet'];
        }

        $count = QUI::getAjax()->callRequestFunction(
            'package_quiqqer_products_ajax_search_backend_execute',
            ['searchParams' => json_encode($searchParams)]
        );

        return [
            'data' => $products,
            'total' => $count['result'],
            'page' => $page
        ];
    },
    ['searchParams'],
    'Permission::checkAdminUser'
);
