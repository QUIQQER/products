<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getVariantFields
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\VariantGenerating;

/**
 * Return all relevant fields for the variants generation
 *
 * @param integer $productId - Product-ID
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getVariantFields',
    function ($productId) {
        $Product = Products::getProduct($productId);

        if (!($Product instanceof VariantParent)) {
            throw new QUI\Exception(['quiqqer/products', 'exception.no.product.parent']);
        }

        $fields = VariantGenerating::getInstance()->getFieldsForGeneration($Product);
        $fields = \array_map(function ($Field) {
            /* @var $Field \QUI\ERP\Products\Field\Field */
            return $Field->getAttributes();
        }, $fields);

        return $fields;
    },
    ['productId'],
    'Permission::checkAdminUser'
);
