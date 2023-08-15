<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_update
 */

use QUI\ERP\Products\Handler\Fields;

/**
 * Update a field
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
        $Field = $Fields->getField($fieldId);
        $params = json_decode($params, true);

        $oldValues = $Field->toProductArray();
        $oldOptions = $Field->getOptions();

        if (isset($params['options'])) {
            $Field->setOptions($params['options']);
        }

        if (isset($params['defaultValue'])) {
            $Field->setDefaultValue($params['defaultValue']);
        }

        $Field->setAttributes($params);
        $Field->save();

        // vererbbar und editiert
        /*
        try {
            $Config    = QUI::getPackage('quiqqer/products')->getConfig();
            $editable  = $Config->getSection('editableFields');
            $inherited = $Config->getSection('inheritedFields');

            if (isset($params['fieldEditable'])) {
                if ((int)$params['fieldEditable'] === 1) {
                    $editable[$Field->getId()] = (int)$params['fieldEditable'];
                } elseif (isset($editable[$Field->getId()])) {
                    unset($editable[$Field->getId()]);
                }

                Products::setGlobalEditableVariantFields(array_keys($editable));
            }

            if (isset($params['fieldInherited'])) {
                if ((int)$params['fieldInherited'] === 1) {
                    $inherited[$Field->getId()] = (int)$params['fieldInherited'];
                } elseif (isset($inherited[$Field->getId()])) {
                    unset($inherited[$Field->getId()]);
                }

                Products::setGlobalInheritedVariantFields(array_keys($inherited));
            }
        } catch (QUI\Exception $Exception) {
        }
        */

        $newValues = $Field->toProductArray();

        if (serialize($oldValues) !== serialize($newValues)) {
            return Fields::PRODUCT_ARRAY_CHANGED;
        }

        // changed options?
        if (serialize($oldOptions) !== serialize($Field->getOptions())) {
            return Fields::PRODUCT_ARRAY_CHANGED;
        }

        return Fields::PRODUCT_ARRAY_UNCHANGED;
    },
    ['fieldId', 'params'],
    'Permission::checkAdminUser'
);
