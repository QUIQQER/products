<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_generate_create
 */

use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Add a variant
 *
 * @param integer $productId - Product-ID
 * @return int|false
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_create',
    function ($productId, $fields) {
        $Product = Products::getProduct($productId);
        $fields  = \json_decode($fields, true);

        if (!($Product instanceof VariantParent)) {
            return false;
        }

        $Variant = $Product->createVariant();

        // set fields
        foreach ($fields as $field => $value) {
            $Variant->getField($field)->setValue($value);
        }

        // set article no
        $parentProductNo = $Product->getFieldValue(FieldHandler::FIELD_PRODUCT_NO);
        $newNumber       = \count($Product->getVariants()) + 1;

        $Variant->getField(FieldHandler::FIELD_PRODUCT_NO)->setValue(
            $parentProductNo.'-'.$newNumber
        );

        $Variant->save();

        return $Variant->getId();
    },
    ['productId', 'fields'],
    'Permission::checkAdminUser'
);
