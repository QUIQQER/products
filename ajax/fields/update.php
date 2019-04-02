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
        $Fields = new Fields();
        $Field  = $Fields->getField($fieldId);
        $params = \json_decode($params, true);

        $oldValues  = $Field->toProductArray();
        $oldOptions = $Field->getOptions();

        if (isset($params['options'])) {
            $Field->setOptions($params['options']);
        }

        if (isset($params['defaultValue'])) {
            $Field->setDefaultValue($params['defaultValue']);
        }

        $Field->setAttributes($params);
        $Field->save();

        $newValues = $Field->toProductArray();

        if (\serialize($oldValues) !== \serialize($newValues)) {
            return Fields::PRODUCT_ARRAY_CHANGED;
        }

        // changed options?
        if (\serialize($oldOptions) !== \serialize($Field->getOptions())) {
            return Fields::PRODUCT_ARRAY_CHANGED;
        }

        return Fields::PRODUCT_ARRAY_UNCHANGED;
    },
    ['fieldId', 'params'],
    'Permission::checkAdminUser'
);
