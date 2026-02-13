<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getStandardFields
 */

/**
 * Returns all system fields
 *
 * @return array
 */

use QUI\ERP\Products\Field\Field;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_getStandardFields',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getStandardFields();
        $result = [];

        /* @var $Field Field */
        foreach ($fields as $Field) {
            $result[] = $Field->getAttributes();
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
