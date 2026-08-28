<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\GenerateVariants
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;
use Throwable;

class GenerateVariants extends AbstractVariantTool
{
    private const MAX_COMBINATIONS = 500;

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $productId,
                array $fields,
                string | null $mode = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.create');
                    $mode = $mode === 'reset' ? 'reset' : 'add';

                    if ($mode === 'reset') {
                        self::checkPermission('product.delete');
                        self::checkPermission('product.edit');
                    }

                    $Parent = self::getVariantParent($productId, true);
                    $fields = self::validateGenerationFields($fields);
                    $beforeIds = self::getVariantIds($Parent);

                    if ($mode === 'reset' && $Parent->getDefaultVariantId() !== false) {
                        $Parent->unsetDefaultVariant();
                        $Parent->save(Server::getRequestUser());
                    }

                    $Parent->generateVariants(
                        $fields,
                        $mode === 'reset'
                            ? VariantParent::GENERATION_TYPE_RESET
                            : VariantParent::GENERATION_TYPE_ADD
                    );
                    $afterIds = self::getVariantIds($Parent);

                    return [
                        'variantParentId' => $Parent->getId(),
                        'mode' => $mode,
                        'defaultVariantId' => $Parent->getDefaultVariantId() ?: null,
                        'combinationCount' => self::countCombinations($fields),
                        'createdVariantIds' => array_values(array_diff($afterIds, $beforeIds)),
                        'deletedVariantIds' => array_values(array_diff($beforeIds, $afterIds)),
                        'variantIds' => $afterIds,
                        'variantCount' => count($afterIds)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_generate',
            description: 'Generates variant combinations. Reset mode deletes existing variants before generation.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId', 'fields'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'fields' => self::generationFieldsSchema(),
                    'mode' => [
                        'type' => 'string',
                        'enum' => ['add', 'reset'],
                        'default' => 'add',
                        'description' => 'Reset also requires product.delete and product.edit.'
                    ]
                ]
            ]
        );
    }

    /**
     * @param array<int, mixed> $fields
     * @return array<int, array{fieldId: int, values: array<int, mixed>}>
     */
    protected static function validateGenerationFields(array $fields): array
    {
        $result = [];
        $seen = [];

        foreach ($fields as $entry) {
            if (!is_array($entry) || empty($entry['fieldId']) || empty($entry['values'])) {
                throw new QUI\Exception('Each generation field requires fieldId and non-empty values.', 400);
            }

            $fieldId = (int)$entry['fieldId'];

            if (isset($seen[$fieldId])) {
                throw new QUI\Exception('Generation field ' . $fieldId . ' occurs more than once.', 400);
            }

            if (!is_array($entry['values'])) {
                throw new QUI\Exception('Generation field values must be an array.', 400);
            }

            $Field = Fields::getField($fieldId);
            self::validateGenerationField($Field);
            $values = array_values(array_unique($entry['values'], SORT_REGULAR));

            foreach ($values as $value) {
                $Field->setValue($value);
            }

            $result[] = ['fieldId' => $fieldId, 'values' => $values];
            $seen[$fieldId] = true;
        }

        if ($result === []) {
            throw new QUI\Exception('At least one generation field is required.', 400);
        }

        if (self::countCombinations($result) > self::MAX_COMBINATIONS) {
            throw new QUI\Exception(
                'Variant generation is limited to ' . self::MAX_COMBINATIONS . ' combinations per call.',
                400
            );
        }

        return $result;
    }

    /**
     * @param array<int, array{fieldId: int, values: array<int, mixed>}> $fields
     */
    protected static function countCombinations(array $fields): int
    {
        $count = 1;

        foreach ($fields as $entry) {
            $count *= count($entry['values']);
        }

        return $count;
    }

    /**
     * @return int[]
     */
    protected static function getVariantIds(VariantParent $Parent): array
    {
        $result = [];
        $variants = $Parent->getVariants();

        if (!is_array($variants)) {
            $variants = [];
        }

        foreach ($variants as $Variant) {
            if ($Variant instanceof VariantChild) {
                $result[] = $Variant->getId();
            }
        }

        sort($result);

        return $result;
    }
}
