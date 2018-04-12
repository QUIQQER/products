<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getStandardFields
 */

/**
 * Returns all system fields
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getStandardFields',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getStandardFields();
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
