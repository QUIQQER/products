<?php

namespace QUI\ERP\Products\Utils;

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\Utils\Singleton;

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
     *
     * @param VariantParent $Product
     * @return array
     */
    public function getFieldsForGeneration(VariantParent $Product)
    {
        $attributeList = $Product->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST);
        $attributes    = $Product->getFieldsByType(Fields::TYPE_ATTRIBUTES);

        $fields = \array_merge($attributes, $attributeList);

        \usort($fields, function ($A, $B) {
            return $A->getId() - $B->getId();
        });

        return $fields;
    }

    /**
     * @param VariantParent $Product
     * @return array
     */
    public function getMissingVariantsList(VariantParent $Product)
    {
        $result = [];

        $children = $Product->getVariants();
        $exists   = array_map(function ($Variant) {
            return $Variant->getAttribute('variant-hash');
        }, $children);

        return $result;
    }
}
