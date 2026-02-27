<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_list
 */

/**
 * Returns field list for a grid
 *
 * @param string $params - JSON query params
 *
 * @return array
 */

use QUI\ERP\Products\Field\Field;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_list',
    function ($params) {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $result = [];
        $params = json_decode($params, true);

        $Grid = new QUI\Utils\Grid();
        $query = $Grid->parseDBParams($params);

        if (!empty($params['type'])) {
            $query['where']['type'] = $params['type'];
        }

        if (!empty($params['showSearchableOnly'])) {
            $query['where']['search_type'] = [
                'type' => 'NOT',
                'value' => ''
            ];
        }

        $data = $Fields->getFields($query);

        foreach ($data as $Field) {
            $attributes = $Field->getAttributes();

            $attributes['suffix'] = method_exists($Field, 'getSuffix') ? $Field->getSuffix() : '';
            $attributes['prefix'] = method_exists($Field, 'getPrefix') ? $Field->getPrefix() : '';

            $result[] = $attributes;
        }

        return $Grid->parseResult($result, $Fields->countFields($query));
    },
    ['params'],
    'Permission::checkAdminUser'
);
