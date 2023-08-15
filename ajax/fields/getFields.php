<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getFields
 */

use QUI\ERP\Products\Handler\Fields;

/**
 * Returns multiple fields
 *
 * @param string $fieldId - Field-IDs JSON
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getFields',
    function ($fieldIds) {
        $fieldIds = \json_decode($fieldIds, true);
        $result = [];

        foreach ($fieldIds as $fieldId) {
            try {
                $Fields = new Fields();
                $result[] = $Fields->getField($fieldId)->getAttributes();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $result;
    },
    ['fieldIds'],
    'Permission::checkAdminUser'
);
