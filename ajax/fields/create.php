<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_create
 */

/**
 * Create a new field
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_create',
    function ($params) {
        $params = json_decode($params, true);
        $Categories = new QUI\ERP\Products\Handler\Fields();

        $Field = $Categories->createField($params);

        return $Field->getAttributes();
    },
    ['params'],
    'Permission::checkAdminUser'
);
