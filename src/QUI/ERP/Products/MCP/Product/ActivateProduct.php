<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\ActivateProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class ActivateProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.activate');
                    $Product = self::getProduct($productId);
                    $Product->activate(Server::getRequestUser());

                    return self::parseProduct($Product, $lang);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_activate',
            description: 'Activates an existing product.',
            inputSchema: self::productIdSchema()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function productIdSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['productId'],
            'properties' => [
                'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Product ID.'],
                'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
            ]
        ];
    }
}
