<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\CreateVariant
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class CreateVariant extends AbstractVariantTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $productId,
                array | null $fieldValues = null,
                string | null $lang = null,
                bool | null $includePrices = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.create');

                    if ($includePrices === true) {
                        self::checkPermission('product.view.prices');
                    }

                    $Parent = self::getVariantParent($productId, true);
                    $fieldValues = self::validateFieldValues($fieldValues ?? []);
                    $Variant = $fieldValues === []
                        ? $Parent->createVariant()
                        : $Parent->generateVariant($fieldValues);

                    return self::parseVariant($Variant, $Parent, $lang, true, $includePrices === true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_create',
            description: 'Creates one child variant, optionally with variant-defining field values.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'fieldValues' => self::fieldValuesSchema(),
                    'lang' => ['type' => 'string'],
                    'includePrices' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Include calculated prices. Requires product.view.prices.'
                    ]
                ]
            ]
        );
    }
}
