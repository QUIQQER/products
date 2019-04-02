<?php

/**
 * This file contains package_quiqqer_products_ajax_fields_getPublicFields
 */

/**
 * Returns all public fields
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getPublicFields',
    function () {
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFields();

        $fields = \array_filter($fields, function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->isPublic();
        });

        $result = [];

        /* @var $Field \QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            $result[] = $Field->getAttributes();
        }

        return $result;
    },
    false
);
