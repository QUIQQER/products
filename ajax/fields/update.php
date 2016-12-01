<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_update
 */

use QUI\ERP\Products\Handler\Fields;

/**
 * Update the a field
 *
 * @param integer $fieldId - Field-ID
 * @param string $params - JSON query params
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_update',
    function ($fieldId, $params) {
        $Fields    = new Fields();
        $Field     = $Fields->getField($fieldId);
        $oldValues = $Field->toProductArray();
        $params    = json_decode($params, true);

        if (isset($params['options'])) {
            $Field->setOptions($params['options']);
        }

        if (isset($params['defaultValue'])) {
            $Field->setDefaultValue($params['defaultValue']);
        }

        $Field->setAttributes($params);
        $Field->save();

        $newValues = $Field->toProductArray();

        if (serialize($oldValues) != serialize($newValues)) {
            return Fields::PRODUCT_ARRAY_CHANGED;
        }

        return Fields::PRODUCT_ARRAY_UNCHANGED;
    },
    array('fieldId', 'params'),
    'Permission::checkAdminUser'
);
