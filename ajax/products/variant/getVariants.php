<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getVariants
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\Utils\Grid;

/**
 * Activate a product
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getVariants',
    function ($productId, $options) {
        $Product = Products::getProduct($productId);
        $options = \json_decode($options, true);

        $page = 1;

        if (isset($options['page'])) {
            $page = (int)$options['page'];
        }

        /* @var $Product VariantParent */
        if (!($Product instanceof VariantParent)) {
            return [];
        }

        $Grid    = new QUI\Utils\Grid();
        $options = $Grid->parseDBParams($options);

        $variants = $Product->getVariants($options);
        $variants = \array_map(function ($Variant) {
            /* @var $Variant \QUI\ERP\Products\Product\Types\VariantChild */
            return $Variant->getAttributes();
        }, $variants);

        // count
        $options['count'] = true;
        $count            = $Product->getVariants($options);

        return [
            'data'  => $variants,
            'page'  => $page,
            'total' => $count
        ];
    },
    ['productId', 'options'],
    'Permission::checkAdminUser'
);
