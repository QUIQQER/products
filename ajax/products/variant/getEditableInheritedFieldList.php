<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getEditableFieldList
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Return the editable variant fields
 *
 * @param integer $productId - Product-ID
 * @param string $options - JSON
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getEditableInheritedFieldList',
    function ($productId, $options = '') {
        // defaults
        $fields = false;
        $options = json_decode($options, true);

        if (!is_array($options)) {
            $options = [];
        }

        if (!isset($options['sortOn'])) {
            $options['sortOn'] = 'id';
        }


        // editable product fields
        $editable = Products::getGlobalEditableVariantFields();
        $editable = array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getId();
        }, $editable);

        // inherited product fields
        $inherited = Products::getGlobalInheritedVariantFields();
        $inherited = array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getId();
        }, $inherited);


        if (!empty($productId)) {
            $Product = Products::getProduct($productId);

            if ($Product->getAttribute('editableVariantFields')) {
                $editable = $Product->getAttribute('editableVariantFields');
            }

            if ($Product->getAttribute('inheritedVariantFields')) {
                $inherited = $Product->getAttribute('inheritedVariantFields');
            }

            if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
                $Parent = $Product->getParent();

                if ($Parent->getAttribute('editableVariantFields')) {
                    $editable = $Parent->getAttribute('editableVariantFields');
                }

                if ($Parent->getAttribute('inheritedVariantFields')) {
                    $inherited = $Parent->getAttribute('inheritedVariantFields');
                }
            }

            // fields
            $fields = $Product->getFields();
        }

        // if $editable is false, then use global erp field settings
        if ($fields === false) {
            $fields = QUI\ERP\Products\Handler\Fields::getFields();
        }

        // sorting
        if (!empty($options['sortOn'])) {
            $fields = QUI\ERP\Products\Utils\Fields::sortFields($fields, $options['sortOn']);

            if (!empty($options['sortBy']) && $options['sortBy'] === 'DESC') {
                $fields = array_reverse($fields);
            }
        }

        // data
        $fields = array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getAttributes();
        }, $fields);

        // pagination
        $count = count($fields);
        $page = 1;
        $perPage = 20;

        if (isset($options['perPage'])) {
            $perPage = (int)$options['perPage'];
        }

        if (isset($options['page'])) {
            $page = (int)$options['page'];
        }

        $fields = array_slice(
            $fields,
            $page * $perPage - $perPage,
            $perPage
        );

        return [
            'inherited' => $inherited,
            'editable' => $editable,
            'fields' => $fields,
            'total' => $count,
            'page' => $page
        ];
    },
    ['productId', 'options'],
    'Permission::checkAdminUser'
);
