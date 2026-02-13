<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getFieldTypeSettings
 */

/**
 * Returns all available extra field settings
 *
 * @return array
 */

use QUI\ERP\Products\Field\Field;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_getFieldTypeSettings',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFields();
        $result = [];

        /* @var $Field Field */
        foreach ($fields as $Field) {
            if (method_exists($Field, 'getJavaScriptSettings')) {
                $settings = $Field->getJavaScriptSettings();

                if (!empty($settings)) {
                    $result[$Field->getId()] = $settings;
                }
            }
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
