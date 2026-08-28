<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\CopyProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class CopyProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.create');

                    return self::parseProduct(Products::copyProduct($productId), $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_copy',
            description: 'Copies an existing product and returns the new product.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Source product ID.'],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
