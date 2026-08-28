<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\GetProductPermissions
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class GetProductPermissions extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.setPermissions');
                    $permissions = self::getProduct($productId)->getPermissions();

                    return [
                        'productId' => $productId,
                        'permissions' => [
                            'viewable' => $permissions['permission.viewable'] ?? null,
                            'buyable' => $permissions['permission.buyable'] ?? null
                        ]
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_permissions_get',
            description: 'Returns the product-specific viewable and buyable user/group permissions.',
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
