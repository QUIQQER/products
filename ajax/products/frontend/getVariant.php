<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getVariant
 */

use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Utils\Package as PackageUtils;
use QUI\ERP\Products\Utils\Products as ProductUtils;

/**
 * Return the product variant html
 *
 * @param string $productId - ID of a product
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVariant',
    function ($productId, $fields, $ignoreDefaultVariant) {
        $cacheName = \QUI\ERP\Products\Handler\Cache::frontendProductCacheName(
            $productId,
            [$fields, $ignoreDefaultVariant]
        );

        // caching only if prices are hidden
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            try {
                return QUI\Cache\LongTermCache::get($cacheName);
            } catch (QUI\Exception $Exception) {
            }
        }

        if (!isset($ignoreDefaultVariant)) {
            $ignoreDefaultVariant = false;
        } else {
            $ignoreDefaultVariant = !!$ignoreDefaultVariant;
        }

        try {
            $Product = Products::getNewProductInstance($productId);

            if ($Product instanceof VariantChild) {
                $Product = $Product->getParent();
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());

            return '';
        }

        $ExceptionStack = new QUI\ExceptionStack();
        $fields = json_decode($fields, true);

        // json js <-> php
        if (count($fields) && is_array($fields[0])) {
            $_fields = [];

            foreach ($fields as $field) {
                $_fields[key($field)] = current($field);
            }

            $fields = $_fields;
        }

        $isVariantParent = false;

        if (!isset($_fields[Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES])) {
            // set variant field values
            foreach ($fields as $fieldId => $fieldValue) {
                try {
                    $Field = $Product->getField($fieldId);

                    if (
                        $Field->getType() === Fields::TYPE_ATTRIBUTE_LIST
                        || $Field->getType() === Fields::TYPE_ATTRIBUTE_GROUPS
                    ) {
                        if (
                            $ignoreDefaultVariant
                            && PackageUtils::getConfig()->getValue('products', 'resetFieldsAction')
                        ) {
                            $Field->clearDefaultValue();
                        }

                        $Field->setValue($fieldValue);
                    }
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());

                    $ExceptionStack->addException($Exception);
                }
            }

            $attributeGroups = $Product->getFieldsByType(Fields::TYPE_ATTRIBUTE_GROUPS);

            if (!$ExceptionStack->isEmpty()) {
                $list = $ExceptionStack->getExceptionList();

                throw new $list[0]();
            }

            try {
                /* @var $Product QUI\ERP\Products\Product\Types\VariantParent */
                $fieldHash = QUI\ERP\Products\Utils\Products::generateVariantHashFromFields($attributeGroups);
                $Child = $Product->getVariantByVariantHash($fieldHash);
            } catch (QUI\Exception $Exception) {
                $Child = $Product;
                $isVariantParent = true;
            }
        } else {
            $childId = (int)$_fields[Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES];

            if (!$childId) {
                $variants = $Product->getVariants();

                if (isset($variants[0])) {
                    $childId = $variants[0]->getId();
                } else {
                    $childId = $Product->getId();
                    $isVariantParent = true;
                }
            }

            $Child = Products::getNewProductInstance($childId);
        }

        $categoryId = null;

        if ($Child->getCategory()) {
            $categoryId = $Child->getCategory()->getId();
        }

        // set attribute lists
        $attributeLists = $Child->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST);

        foreach ($attributeLists as $Field) {
            if (isset($fields[$Field->getId()])) {
                try {
                    $Child->getField($Field->getId())->setValue($fields[$Field->getId()]);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }

        // render
        $Control = new ProductControl([
            'Product' => $Child,
            'ignoreDefaultVariant' => $ignoreDefaultVariant
        ]);

        $url = '';

        try {
            $url = $Child->getUrlRewrittenWithHost();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $result = [
            'variantId' => $Child->getId(),
            'control' => QUI\Output::getInstance()->parse($Control->create()),
            'css' => QUI\Control\Manager::getCSS(),
            'url' => $url,
            'title' => $Child->getTitle(),
            'category' => $categoryId,
            'fieldHashes' => ProductUtils::getJsFieldHashArray($Product),
            'availableHashes' => array_flip($Product->availableActiveFieldHashes()),
            'isVariantParent' => $isVariantParent
        ];

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            QUI\Cache\LongTermCache::set($cacheName, $result);
        }

        return $result;
    },
    ['productId', 'fields', 'ignoreDefaultVariant']
);
