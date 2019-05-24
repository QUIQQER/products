<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getOverwritableFieldList
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getOverwritableFieldList',
    function ($productId, $options = '') {
        // defaults
        $overwritable = false;
        $fields       = false;
        $options      = \json_decode($options, true);

        if (!\is_array($options)) {
            $options = [];
        }

        if (!isset($options['sortOn'])) {
            $options['sortOn'] = 'id';
        }


        // product fields
        if (!empty($productId)) {
            $Product      = Products::getProduct($productId);
            $overwritable = $Product->getAttribute('overwritableVariantFields');

            // fields
            $fields = $Product->getFields();
        } else {
            $overwritable = Products::getGlobalOverwritableVariantFields();
            $overwritable = \array_map(function ($Field) {
                /* @var $Field \QUI\ERP\Products\Field\Field */
                return $Field->getId();
            }, $overwritable);
        }

        // if $overwritable is false, then use global erp field settings
        if ($overwritable === false || $fields === false) {
            $fields = \QUI\ERP\Products\Handler\Fields::getFields();
        }

        // sorting
        if (!empty($options['sortOn'])) {
            $fields = QUI\ERP\Products\Utils\Fields::sortFields($fields, $options['sortOn']);

            if (!empty($options['sortBy']) && $options['sortBy'] === 'DESC') {
                $fields = \array_reverse($fields);
            }
        }

        // data
        $fields = \array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getAttributes();
        }, $fields);

        // pagination
        $count   = \count($fields);
        $page    = 1;
        $perPage = 20;

        if (isset($options['perPage'])) {
            $perPage = (int)$options['perPage'];
        }

        if (isset($options['page'])) {
            $page = (int)$options['page'];
        }

        $fields = \array_slice(
            $fields,
            $page * $perPage - $perPage,
            $perPage
        );

        return [
            'overwritable' => $overwritable,
            'fields'       => $fields,
            'total'        => $count,
            'page'         => $page
        ];
    },
    ['productId', 'options'],
    'Permission::checkAdminUser'
);
