<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getFieldTypeSettings
 */

/**
 * Returns all available extra field settings
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getFieldTypeSettings',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFields();
        $result = array();

        /* @var $Field \QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if (method_exists($Field, 'getJavaScriptSettings')) {
                $result[$Field->getType()] = $Field->getJavaScriptSettings();
            }
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
