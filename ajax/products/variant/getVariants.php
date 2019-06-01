<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getVariants
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Return the variant list of a product
 *
 * @param integer $productId - Product-ID
 * @param string $options - JSON Array - Grid options
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_getVariants',
    function ($productId, $options) {
        $Product = Products::getProduct($productId);
        $options = \json_decode($options, true);

        $page = 1;

        if (isset($options['page']) && (int)$options['page']) {
            $page = (int)$options['page'];
        }

        /* @var $Product VariantParent */
        if (!($Product instanceof VariantParent)) {
            return [];
        }

        $Grid         = new QUI\Utils\Grid();
        $queryOptions = $Grid->parseDBParams($options);

        $variants = $Product->getVariants($queryOptions);
        $variants = \array_map(function ($Variant) use ($Product) {
            /* @var $Variant \QUI\ERP\Products\Product\Types\VariantChild */
            $attributes        = $Variant->getAttributes();
            $attributes['url'] = $Variant->getUrl();

            $attributes['defaultVariant']      = 0;
            $attributes['price_netto_display'] = QUI\ERP\Defaults::getCurrency()->format(
                $attributes['price_netto']
            );

            if ($Product->getDefaultVariantId() === $Variant->getId()) {
                $attributes['defaultVariant'] = 1;
            }


            return $attributes;
        }, $variants);

        // count
        $queryOptions['count'] = true;
        $count                 = $Product->getVariants($queryOptions);

        return [
            'data'  => $variants,
            'page'  => $page,
            'total' => $count
        ];
    },
    ['productId', 'options'],
    'Permission::checkAdminUser'
);
