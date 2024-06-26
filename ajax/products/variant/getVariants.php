<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_getVariants
 */

use QUI\ERP\Products\Field\Types\AttributeGroup;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\Tables as ProductTables;

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
        $options = json_decode($options, true);
        $lang = QUI::getLocale()->getCurrent();
        $Currency = QUI\ERP\Defaults::getCurrency();
        $page = 1;
        $defaultVariantId = -1;

        if (isset($options['page']) && (int)$options['page']) {
            $page = (int)$options['page'];
        }

        /* @var $Product VariantParent */
        if (!($Product instanceof VariantParent)) {
            return [];
        }

        $Grid = new QUI\Utils\Grid();
        $queryOptions = $Grid->parseDBParams($options);

        if ($options === null) {
            $queryOptions = [];
        }

        // variants w search cache
        $children = QUI::getDataBase()->fetch([
            'select' => ['id', 'parent'],
            'from' => ProductTables::getProductTableName(),
            'where' => [
                'parent' => $Product->getId()
            ]
        ]);

        $childrenIds = array_column($children, 'id');
        $searchResultIds = [];
        $searchResult = [];

        if (!empty($childrenIds)) {
            $queryOptions['select'] = '*';
            $queryOptions['from'] = ProductTables::getProductCacheTableName();
            $queryOptions['where'] = [
                'lang' => QUI::getLocale()->getCurrent(),
                'id' => [
                    'type' => 'IN',
                    'value' => $childrenIds
                ]
            ];

            $defaultVariantId = $Product->getDefaultVariantId();

            if ($Product->getCurrency()) {
                $Currency = $Product->getCurrency();
            }

            $searchResult = QUI::getDataBase()->fetch($queryOptions);

            // get field data for AttributeGroup fields for every found VariantChild
            $searchResultIds = array_column($searchResult, 'id');
        }

        $childFieldData = [];
        $parentAttributeGroupFields = $Product->getFieldsByType([
            'AttributeGroup'
        ]);
        $parentAttributeGroupFieldOptions = [];

        /** @var AttributeGroup $ParentAttributeGroupField */
        foreach ($parentAttributeGroupFields as $ParentAttributeGroupField) {
            $fieldOptions = $ParentAttributeGroupField->getOptions();

            if (empty($fieldOptions['entries'])) {
                continue;
            }

            $fieldTitlesByValue = [];

            foreach ($fieldOptions['entries'] as $entry) {
                if (empty($entry['title'][$lang])) {
                    $title = current($entry['title']);
                } else {
                    $title = $entry['title'][$lang];
                }

                $fieldTitlesByValue[$entry['valueId']] = $title;
            }

            $parentAttributeGroupFieldOptions[$ParentAttributeGroupField->getId()] = $fieldTitlesByValue;
        }

        $parentAttributeGroupFieldIds = array_map(function ($AttributeGroupField) {
            /** @var AttributeGroup $AttributeGroupField */
            return $AttributeGroupField->getId();
        }, $parentAttributeGroupFields);

        if (!empty($searchResultIds)) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'fieldData'],
                'from' => ProductTables::getProductTableName(),
                'where' => [
                    'id' => [
                        'type' => 'IN',
                        'value' => $searchResultIds
                    ]
                ]
            ]);

            foreach ($result as $row) {
                $childId = $row['id'];
                $fieldData = json_decode($row['fieldData'], true);
                $attributeGroupTitlesByValue = [];

                // AttributeGroup fields only!
                $fieldData = array_filter($fieldData, function ($entry) use ($parentAttributeGroupFieldIds) {
                    return in_array($entry['id'], $parentAttributeGroupFieldIds);
                });

                foreach ($fieldData as $field) {
                    $fieldId = $field['id'];

                    if (empty($parentAttributeGroupFieldOptions[$fieldId][$field['value']])) {
                        $title = '-';
                    } else {
                        $title = $parentAttributeGroupFieldOptions[$fieldId][$field['value']];
                    }

                    $attributeGroupTitlesByValue[$field['id']] = $title;
                }

                $childFieldData[$childId] = $attributeGroupTitlesByValue;
            }
        }

        $variants = [];

        $defaultFields = [
            Fields::FIELD_PRICE,
            Fields::FIELD_PRODUCT_NO,
            Fields::FIELD_PRIORITY
        ];

        if (!empty($searchResult)) {
            $variants = array_map(function ($entry) use (
                $defaultVariantId,
                $Currency,
                $childFieldData,
                $defaultFields
            ) {
                $variantId = (int)$entry['id'];
                $fields = [];
                $addedFields = [];

                foreach ($entry as $k => $v) {
                    if (!str_starts_with($k, 'F')) {
                        continue;
                    }

                    $fieldId = (int)mb_substr($k, 1);

                    if (isset($addedFields[$fieldId])) {
                        continue;
                    }

                    if (isset($childFieldData[$variantId][$fieldId]) || in_array($fieldId, $defaultFields)) {
                        $fields[] = [
                            'id' => $fieldId,
                            'value' => $v,
                            'title' => !empty($childFieldData[$variantId][$fieldId]) ?
                                $childFieldData[$variantId][$fieldId] :
                                null
                        ];

                        $addedFields[$fieldId] = true;
                    }
                }

                // add values of AttributeGroup fields
                foreach ($childFieldData[$variantId] as $fieldId => $title) {
                    if (isset($addedFields[$fieldId])) {
                        continue;
                    }

                    $fields[] = [
                        'id' => $fieldId,
                        'value' => $title,
                        'title' => $title
                    ];

                    $addedFields[$fieldId] = true;
                }

                return [
                    'id' => $variantId,
                    'active' => (int)$entry['active'],
                    'productNo' => $entry['productNo'],
                    'fields' => $fields,
                    'defaultVariant' => $defaultVariantId === (int)$entry['id'] ? 1 : 0,

                    'description' => $entry['F' . Fields::FIELD_SHORT_DESC],
                    'title' => $entry['title'],
                    'e_date' => $entry['e_date'],
                    'c_date' => $entry['c_date'],
                    'priority' => $entry['F' . Fields::FIELD_PRIORITY],
                    'url' => $entry['F' . Fields::FIELD_URL],
                    'price_netto_display' => $Currency->format($entry['F' . Fields::FIELD_PRICE])
                ];
            }, $searchResult);
        }

        // count
        $queryOptions['count'] = true;
        $count = $Product->getVariants($queryOptions);

        return [
            'data' => $variants,
            'page' => $page,
            'total' => $count
        ];
    },
    ['productId', 'options'],
    'Permission::checkAdminUser'
);
