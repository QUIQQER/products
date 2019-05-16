<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getVariant
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;

/**
 * Return the product variant html
 *
 * @param string $productId - ID of a product
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVariant',
    function ($productId, $fields) {
        try {
            $Product = Products::getNewProductInstance($productId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return '';
        }

        $ExceptionStack = new QUI\ExceptionStack();
        $fields         = \json_decode($fields, true);

        // json js <-> php
        if (count($fields) && is_array($fields[0])) {
            $_fields = [];

            foreach ($fields as $field) {
                $_fields[key($field)] = current($field);
            }

            $fields = $_fields;
        }

        foreach ($fields as $fieldId => $fieldValue) {
            try {
                $Product->getField($fieldId)->setValue($fieldValue);
                $fields[$fieldId] = $Product->getField($fieldId)->getValue();
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            $list = $ExceptionStack->getExceptionList();

            throw new $list[0];
        }


        try {
            /* @var $Product \QUI\ERP\Products\Product\Types\VariantParent */
            $fieldHash = \QUI\ERP\Products\Utils\Products::generateVariantHashFromFields($fields);
            $Child     = $Product->getVariantByVariantHash($fieldHash);
        } catch (QUI\Exception $Exception) {
            $Child = $Product;
        }

        $Control = new ProductControl([
            'Product' => $Child
        ]);

        return $Control->create();
    },
    ['productId', 'fields']
);
