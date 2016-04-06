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
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_update',
    function ($fieldId, $params) {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $Field  = $Fields->getField($fieldId);
        $params = json_decode($params, true);

        if (isset($params['options'])) {
            $Field->setOptions($params['options']);
        }

        $Field->setAttributes($params);
        $Field->save();
    },
    array('fieldId', 'params'),
    'Permission::checkAdminUser'
);
