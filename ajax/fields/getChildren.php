<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getChildren
 */

/**
 * Returns a field list
 *
 * @param string $fieldIds - Field-ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getChildren',
    function ($fieldIds) {
        $Fields   = new QUI\ERP\Products\Handler\Fields();
        $fieldIds = \json_decode($fieldIds, true);
        $result   = [];

        if (!\is_array($fieldIds)) {
            $fieldIds = [];
        }

        foreach ($fieldIds as $fieldId) {
            try {
                $Field    = $Fields->getField($fieldId);
                $result[] = $Field->getAttributes();
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    },
    ['fieldIds'],
    'Permission::checkAdminUser'
);
