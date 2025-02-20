<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_search
 */

/**
 * Returns category list
 *
 * @param string $params - JSON query params
 *
 * @return array
 */

use QUI\ERP\Products\Category\AllProducts;
use QUI\ERP\Products\Category\Category;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_search',
    function ($fields, $params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $result = [];

        $query = [];
        $params = json_decode($params, true);
        $fields = json_decode($fields, true);

        if (!is_array($fields)) {
            $fields = [];
        }

        if (isset($fields['limit'])) {
            $query['limit'] = $fields['limit'];
        }

        $allowedFields = $Categories->getChildAttributes();
        $allowedFields = array_flip($allowedFields);

        $searchString = '';

        foreach ($params as $field => $value) {
            if (!isset($allowedFields[$field]) && $field != 'id') {
                continue;
            }

            $query['where_or'][$field] = [
                'type' => '%LIKE%',
                'value' => $value
            ];

            if ($field === 'fields') {
                $query['where_or']['title_cache'] = [
                    'type' => '%LIKE%',
                    'value' => $value
                ];

                $query['where_or']['description_cache'] = [
                    'type' => '%LIKE%',
                    'value' => $value
                ];

                $searchString = $value;
            }
        }

        // search
        $data = $Categories->getCategories($query);

        /* @var $Category Category */
        foreach ($data as $Category) {
            $entry = $Category->getAttributes();
            $entry['title'] = $Category->getTitle();

            $result[] = $entry;
        }

        usort($result, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        // all products at the beginning
        $AllProducts = new AllProducts();

        if (!empty($searchString) && stripos($AllProducts->getTitle(), $searchString) !== false) {
            $allProducts = $AllProducts->getAttributes();
            $allProducts['title'] = $AllProducts->getTitle();

            array_unshift($result, $allProducts);
        }

        return $result;
    },
    ['fields', 'params'],
    'Permission::checkAdminUser'
);
