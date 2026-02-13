<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_search
 */

/**
 * Returns field list
 *
 * @param string $params - JSON query params
 *
 * @return array
 */

use QUI\ERP\Products\Field\Field;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_search',
    function ($fields, $params) {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $result = [];

        $query = [];
        $params = json_decode($params, true);
        $fields = json_decode($fields, true);

        if (!is_array($fields)) {
            $fields = [];
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        $allowedFields = $Fields->getChildAttributes();
        $allowedFields = array_flip($allowedFields);

        foreach ($fields as $field => $value) {
            if (!isset($allowedFields[$field]) && $field != 'id') {
                continue;
            }

            $query['where_or'][$field] = [
                'type' => '%LIKE%',
                'value' => $value
            ];
        }

        // search
        $data = $Fields->getFields($query);

        foreach ($data as $Field) {
            $entry = $Field->getAttributes();
            $entry['title'] = $Field->getTitle();

            $result[] = $entry;
        }

        usort($result, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        return $result;
    },
    ['fields', 'params'],
    'Permission::checkAdminUser'
);
