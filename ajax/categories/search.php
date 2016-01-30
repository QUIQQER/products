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
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_search',
    function ($fields, $params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $result     = array();

        $query  = array();
        $params = json_decode($params, true);
        $fields = json_decode($fields, true);

        if (!is_array($fields)) {
            $fields = array();
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        $allowedFields = $Categories->getChildAttributes();
        $allowedFields = array_flip($allowedFields);

        foreach ($fields as $field => $value) {
            if (!isset($allowedFields[$field]) && $field != 'id') {
                continue;
            }

            $query['where_or'][$field] = array(
                'type' => '%LIKE%',
                'value' => $value
            );
        }

        // search
        $data = $Categories->getCategories($query);

        /* @var $Category \QUI\ERP\Products\Category\Category */
        foreach ($data as $Category) {
            $entry          = $Category->getAttributes();
            $entry['title'] = $Category->getTitle();

            $result[] = $entry;
        }

        usort($result, function ($a, $b) {
            return $a['title'] > $b['title'];
        });

        return $result;
    },
    array('fields', 'params'),
    'Permission::checkAdminUser'
);
