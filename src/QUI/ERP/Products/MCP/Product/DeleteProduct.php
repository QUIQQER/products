<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\DeleteProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class DeleteProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.delete');
                    self::getProduct($productId)->delete();

                    return ['productId' => $productId, 'deleted' => true];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_delete',
            description: 'Permanently deletes an existing product.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Product ID.']
                ]
            ]
        );
    }
}
