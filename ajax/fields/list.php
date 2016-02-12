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
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_list',
    function ($params) {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $result = array();

        $Grid = new QUI\Utils\Grid();

        $data = $Fields->getFields(
            $Grid->parseDBParams(json_decode($params, true))
        );

        /* @var $Field \QUI\ERP\Products\Field\Field */
        foreach ($data as $Field) {
            $result[] = $Field->getAttributes();
        }

        return $Grid->parseResult($result, $Fields->countFields());
    },
    array('params'),
    'Permission::checkAdminUser'
);
