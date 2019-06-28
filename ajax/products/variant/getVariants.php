<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getVariants
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\Tables;
use QUI\ERP\Products\Handler\Fields;

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

        // variants w search cache
        $children = QUI::getDataBase()->fetch([
            'select' => ['id', 'parent'],
            'from'   => Tables::getProductTableName(),
            'where'  => [
                'parent' => $Product->getId()
            ]
        ]);

        $childrenIds = \array_map(function ($variant) {
            return $variant['id'];
        }, $children);

        $queryOptions['select'] = '*';
        $queryOptions['from']   = QUI\ERP\Products\Utils\Tables::getProductCacheTableName();
        $queryOptions['where']  = [
            'lang' => QUI::getLocale()->getCurrent(),
            'id'   => [
                'type'  => 'IN',
                'value' => $childrenIds
            ]
        ];

        $defaultVariantId = $Product->getDefaultVariantId();
        $Currency         = $Product->getCurrency();

        if (!$Currency) {
            $Currency = QUI\ERP\Defaults::getCurrency();
        }

        $searchResult = QUI::getDataBase()->fetch($queryOptions);

        $variants = \array_map(function ($entry) use ($defaultVariantId, $Currency) {
            $fields = [];

            foreach ($entry as $k => $v) {
                if (\strpos($k, 'F') !== 0) {
                    continue;
                }

                $fields[] = [
                    'id'    => (int)\mb_substr($k, 1),
                    'value' => $v
                ];
            }

            $attributes = [
                'id'             => $entry['id'],
                'active'         => $entry['active'],
                'productNo'      => $entry['productNo'],
                'fields'         => $fields,
                'defaultVariant' => $defaultVariantId === (int)$entry['id'] ? 1 : 0,

                'description'         => $entry['F'.Fields::FIELD_SHORT_DESC],
                'title'               => $entry['title'],
                'e_date'              => $entry['e_date'],
                'c_date'              => $entry['c_date'],
                'priority'            => $entry['F'.Fields::FIELD_PRIORITY],
                'url'                 => $entry['F'.Fields::FIELD_URL],
                'price_netto_display' => $Currency->format($entry['F'.Fields::FIELD_PRICE])
            ];

            return $attributes;
        }, $searchResult);

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
