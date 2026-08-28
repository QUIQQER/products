<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\GetVariantInheritance
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetVariantInheritance extends AbstractVariantTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    return self::getInheritanceData(self::getVariantParent($productId, true), $lang);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_inheritance_get',
            description: 'Returns effective editable and inherited field settings for a variant parent.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'lang' => ['type' => 'string']
                ]
            ]
        );
    }
}
