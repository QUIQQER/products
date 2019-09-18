<?php

/**
 * This file contains package_quiqqer_products_ajax_products_create
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Create a new product
 *
 * @param string $categories - JSON categories
 * @param string $fields - JSON fields
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_create',
    function ($categories, $fields, $productType) {
        $fields     = \json_decode($fields, true);
        $categories = \json_decode($categories, true);
        $fieldList  = [];

        foreach ($fields as $fieldId => $fieldData) {
            try {
                $fieldId = (int)\str_replace('field-', '', $fieldId);

                $Field = Fields::getField($fieldId);
                $Field->setValue($fieldData);

                $fieldList[] = $Field;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeRecursive($Exception->getMessage());
            }
        }

        $Product = Products::createProduct(
            $categories,
            $fieldList,
            $productType
        );

        return $Product->getAttributes();
    },
    ['categories', 'fields', 'productType'],
    'Permission::checkAdminUser'
);
