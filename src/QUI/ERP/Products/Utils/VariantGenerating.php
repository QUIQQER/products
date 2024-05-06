<?php

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\Utils\Singleton;

use function array_map;
use function array_merge;

/**
 * Class VariantGenerating
 * - Helper to generate children for the variant parent
 *
 * @package QUI\ERP\Products\Utils
 */
class VariantGenerating extends Singleton
{
    /**
     * Return all relevant fields for the variants generation
     * The are fields which are also assigned to the product
     *
     * @param VariantParent $Product
     * @return FieldInterface[]
     */
    public function getFieldsForGeneration(VariantParent $Product): array
    {
        $attributes = $Product->getFieldsByType(Fields::TYPE_ATTRIBUTES);

        return QUI\ERP\Products\Utils\Fields::sortFields($attributes, 'id');
    }

    /**
     * Return all relevant fields for the variants generation
     * They are all available fields, the fields don't need to be assigned to the product
     *
     * These fields are mostly needed for variant generation
     *
     * @return FieldInterface[]
     */
    public function getAvailableFieldsForGeneration(): array
    {
        $attributeList = Fields::getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST);
        $attributes = Fields::getFieldsByType(Fields::TYPE_ATTRIBUTES);
        $fields = array_merge($attributes, $attributeList);

        return QUI\ERP\Products\Utils\Fields::sortFields($fields, 'id');
    }

    /**
     * @param VariantParent $Product
     * @return array
     * @todo was ist hiermit?
     */
    public function getMissingVariantsList(VariantParent $Product): array
    {
        $result = [];

        $children = $Product->getVariants();
        $exists = array_map(function ($Variant) {
            return $Variant->getAttribute('variantHash');
        }, $children);

        return $result;
    }
}
