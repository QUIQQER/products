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
    function ($categories, $fields) {
        $fields     = json_decode($fields, true);
        $categories = json_decode($categories, true);

        $fieldList = array();

        foreach ($fields as $fieldData) {
            try {
                $Field = Fields::getField($fieldData['fieldId']);
                $Field->setValue($fieldData['value']);

                $fieldList[] = $Field;
            } catch (QUI\Exception $Exception) {
            }
        }

        $Products = new Products();
        $Product  = $Products->createProduct($categories, $fieldList);

        return $Product->getAttributes();
    },
    array('categories', 'fields'),
    'Permission::checkAdminUser'
);
