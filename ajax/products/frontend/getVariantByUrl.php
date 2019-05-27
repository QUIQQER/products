<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getVariantByUrl
 */

use QUI\ERP\Products;
use QUI\ERP\Products\Handler\Products as ProductHandler;
use QUI\ERP\Products\Handler\Fields as FieldsHandler;

/**
 * Return the variant information via its variant child url
 *
 * @param string $productId - ID of a product
 * @param string $url - URL of the child
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVariantByUrl',
    function ($productId, $url) {
        try {
            $Product = ProductHandler::getNewProductInstance($productId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return '';
        }

        if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $Product = $Product->getParent();
        }

        if (!($Product instanceof QUI\ERP\Products\Product\Types\VariantParent)) {
            return '';
        }

        $url        = trim($url, '/');
        $categoryId = $Product->getCategory()->getId();
        $Variant    = QUI\ERP\Products\Handler\Products::getProductByUrl($url, $categoryId);

        $attributeGroups = $Variant->getFieldsByType(FieldsHandler::TYPE_ATTRIBUTE_GROUPS);
        $attributeLists  = $Variant->getFieldsByType(FieldsHandler::TYPE_ATTRIBUTE_LIST);

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
            'fields'    => $fields
        ];
    },
    ['productId', 'fields']
);
