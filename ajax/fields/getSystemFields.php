<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getSystemFields
 */

/**
 * Returns all system fields
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getSystemFields',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getSystemFields();
        $result = [];

        /* @var $Field \QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            $result[] = $Field->getAttributes();
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
