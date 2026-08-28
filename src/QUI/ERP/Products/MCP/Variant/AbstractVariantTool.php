<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\AbstractVariantTool
 */

namespace QUI\ERP\Products\MCP\Variant;

use QUI;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\MCP\AbstractTool;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

abstract class AbstractVariantTool extends AbstractTool
{
    protected static function getVariantParent(int $productId, bool $resolveChild = false): VariantParent
    {
        $Product = self::getProduct($productId);

        if ($resolveChild && $Product instanceof VariantChild) {
            return $Product->getParent();
        }

        if (!$Product instanceof VariantParent) {
            throw new QUI\Exception('Product ' . $productId . ' is not a variant parent.', 400);
        }

        return $Product;
    }

    protected static function getVariantChild(int $variantId, VariantParent $Parent): VariantChild
    {
        $Variant = self::getProduct($variantId);

        if (!$Variant instanceof VariantChild || $Variant->getParent()->getId() !== $Parent->getId()) {
            throw new QUI\Exception(
                'Product ' . $variantId . ' is not a variant of product ' . $Parent->getId() . '.',
                400
            );
        }

        return $Variant;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseVariant(
        VariantChild $Variant,
        VariantParent $Parent,
        ?string $lang,
        bool $includeFields,
        bool $includePrices
    ): array {
        $result = self::parseProduct($Variant, $lang, $includeFields, $includePrices);
        $defaultVariantId = $Parent->getDefaultVariantId();
        $result['variantParentId'] = $Parent->getId();
        $result['defaultVariant'] = $defaultVariantId !== false && $defaultVariantId === $Variant->getId();
        $result['variantHash'] = $Variant->getAttribute('variantHash') ?: null;

        return $result;
    }

    /**
     * @param array<int|string, mixed> $fieldValues
     * @return array<int, mixed>
     */
    protected static function validateFieldValues(array $fieldValues): array
    {
        $result = [];

        foreach ($fieldValues as $fieldId => $value) {
            if (!is_numeric($fieldId) || (int)$fieldId < 1) {
                throw new QUI\Exception('Variant field IDs must be positive integers.', 400);
            }

            $Field = Fields::getField((int)$fieldId);
            self::validateGenerationField($Field);
            $Field->setValue($value);
            $result[$Field->getId()] = $value;
        }

        return $result;
    }

    protected static function validateGenerationField(Field $Field): void
    {
        if (!in_array($Field->getType(), [Fields::TYPE_ATTRIBUTES, Fields::TYPE_ATTRIBUTE_LIST], true)) {
            throw new QUI\Exception(
                'Field ' . $Field->getId() . ' cannot be used to generate variants.',
                400
            );
        }

        if ($Field->getOption('exclude_from_variant_generation')) {
            throw new QUI\Exception(
                'Field ' . $Field->getId() . ' is excluded from variant generation.',
                400
            );
        }
    }

    /**
     * @param array<int, mixed> $fieldIds
     * @return int[]
     */
    protected static function validateParentFieldIds(VariantParent $Parent, array $fieldIds): array
    {
        $parentFieldIds = [];

        foreach ($Parent->getFields() as $Field) {
            $parentFieldIds[$Field->getId()] = true;
        }

        $result = [];

        foreach (array_values(array_unique(array_map('intval', $fieldIds))) as $fieldId) {
            if ($fieldId < 1 || !isset($parentFieldIds[$fieldId])) {
                throw new QUI\Exception(
                    'Field ' . $fieldId . ' is not assigned to variant parent ' . $Parent->getId() . '.',
                    400
                );
            }

            $result[] = $fieldId;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getInheritanceData(VariantParent $Parent, ?string $lang = null): array
    {
        $editableAttribute = $Parent->getAttribute('editableVariantFields');
        $inheritedAttribute = $Parent->getAttribute('inheritedVariantFields');
        $usesGlobalEditable = !is_array($editableAttribute);
        $usesGlobalInherited = !is_array($inheritedAttribute);
        $editable = $usesGlobalEditable
            ? array_map(static fn (Field $Field): int => $Field->getId(), Products::getGlobalEditableVariantFields())
            : array_values(array_map('intval', $editableAttribute));
        $inherited = $usesGlobalInherited
            ? array_map(static fn (Field $Field): int => $Field->getId(), Products::getGlobalInheritedVariantFields())
            : array_values(array_map('intval', $inheritedAttribute));
        $Locale = self::getLocale($lang);
        $fields = [];

        foreach ($Parent->getFields() as $Field) {
            $fieldId = $Field->getId();
            $fields[] = [
                'id' => $fieldId,
                'type' => $Field->getType(),
                'title' => $Field->getTitle($Locale),
                'editable' => in_array($fieldId, $editable, true),
                'inherited' => in_array($fieldId, $inherited, true)
            ];
        }

        return [
            'variantParentId' => $Parent->getId(),
            'editableFieldIds' => $editable,
            'inheritedFieldIds' => $inherited,
            'usesGlobalEditableFields' => $usesGlobalEditable,
            'usesGlobalInheritedFields' => $usesGlobalInherited,
            'fields' => $fields
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function fieldValuesSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Variant-defining values keyed by numeric product-field ID.'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function generationFieldsSchema(): array
    {
        return [
            'type' => 'array',
            'minItems' => 1,
            'maxItems' => 20,
            'items' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['fieldId', 'values'],
                'properties' => [
                    'fieldId' => ['type' => 'integer', 'minimum' => 1],
                    'values' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'maxItems' => 100,
                        'uniqueItems' => true
                    ]
                ]
            ]
        ];
    }
}
