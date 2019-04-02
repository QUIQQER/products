<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_deleteChildren
 */

/**
 * Delete fields
 *
 * @param string $fieldIds - JSON list of field ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_deleteChildren',
    function ($fieldIds) {
        $fieldIds = \json_decode($fieldIds, true);
        $Fields   = new QUI\ERP\Products\Handler\Fields();

        foreach ($fieldIds as $fieldId) {
            try {
                $Field = $Fields->getField($fieldId);
                $Field->delete();
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addAttention($Exception->getMessage());
            }
        }
    },
    ['fieldIds'],
    'Permission::checkAdminUser'
);
