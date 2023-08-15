<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getAvailableVariantFields
 */

use QUI\ERP\Products\Utils\VariantGenerating;

/**
 * Return all fields for the variants generation
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getAvailableVariantFields',
    function () {
        $fields = VariantGenerating::getInstance()->getAvailableFieldsForGeneration();
        $fields = \array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getAttributes();
        }, $fields);

        return $fields;
    },
    [],
    'Permission::checkAdminUser'
);
