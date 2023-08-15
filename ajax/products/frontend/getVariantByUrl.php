<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getVariantByUrl
 */

use QUI\ERP\Products;
use QUI\ERP\Products\Handler\Fields as FieldsHandler;
use QUI\ERP\Products\Handler\Products as ProductHandler;

/**
 * Return the variant information via its variant child url
 *
 * @param string $productId - ID of a product
 * @param string $url - URL of the child
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVariantByUrl',
    function ($productId, $variantUrl, $variantId) {
        $Variant = null;

        if (!empty($variantId)) {
            try {
                $Variant = ProductHandler::getNewProductInstance($variantId);
            } catch (QUI\Exception $Exception) {
                return [];
            }
        }

        if ($Variant === null) {
            try {
                $Product = ProductHandler::getNewProductInstance($productId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());

                return [];
            }

            if ($Product instanceof Products\Product\Types\VariantChild) {
                $Product = $Product->getParent();
            }

            if (!($Product instanceof Products\Product\Types\VariantParent)) {
                return [];
            }

            $variantUrl = \trim($variantUrl, '/');
            $categoryId = $Product->getCategory()->getId();

            try {
                $Variant = ProductHandler::getProductByUrl($variantUrl, $categoryId);
            } catch (Products\Product\Exception $Exception) {
                $Variant = $Product;
            }
        }

        $attributeGroups = $Variant->getFieldsByType(FieldsHandler::TYPE_ATTRIBUTE_GROUPS);
        $attributeLists = $Variant->getFieldsByType(FieldsHandler::TYPE_ATTRIBUTE_LIST);

        $fields = [];

        /* @var $Field Products\Field\Field */
        foreach ($attributeGroups as $Field) {
            $fields[$Field->getId()] = $Field->getValue();
        }

        foreach ($attributeLists as $Field) {
            $fields[$Field->getId()] = $Field->getValue();
        }

        return [
            'productId' => $Variant->getId(),
            'fields' => $fields
        ];
    },
    ['productId', 'variantUrl', 'variantId']
);
