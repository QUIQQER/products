<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\DeactivateProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class DeactivateProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.activate');
                    $Product = self::getProduct($productId);
                    $Product->deactivate(Server::getRequestUser());

                    return self::parseProduct($Product, $lang);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_deactivate',
            description: 'Deactivates an existing product.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Product ID.'],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
