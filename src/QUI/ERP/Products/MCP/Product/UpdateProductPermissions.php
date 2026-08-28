<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\UpdateProductPermissions
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class UpdateProductPermissions extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, array $permissions): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.setPermissions');
                    $Product = self::getProduct($productId);
                    $User = Server::getRequestUser();
                    $mapped = [];

                    foreach (['viewable', 'buyable'] as $permission) {
                        if (!array_key_exists($permission, $permissions)) {
                            continue;
                        }

                        $value = (string)$permissions[$permission];

                        if (!QUI\Utils\UserGroups::isUserGroupString($value)) {
                            throw new QUI\Exception('Invalid user/group permission string for ' . $permission . '.', 400);
                        }

                        $mapped['permission.' . $permission] = $value;
                    }

                    $Product->clearPermissions($User);
                    $Product->setPermissions($mapped, $User);
                    $Product->savePermissions($User);

                    return [
                        'productId' => $productId,
                        'permissions' => [
                            'viewable' => $mapped['permission.viewable'] ?? null,
                            'buyable' => $mapped['permission.buyable'] ?? null
                        ]
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_permissions_update',
            description: 'Replaces the product-specific viewable and buyable user/group permissions.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId', 'permissions'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Product ID.'],
                    'permissions' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'description' => 'Complete replacement. An empty object clears both product permissions.',
                        'properties' => [
                            'viewable' => ['type' => 'string', 'description' => 'QUIQQER user/group string.'],
                            'buyable' => ['type' => 'string', 'description' => 'QUIQQER user/group string.']
                        ]
                    ]
                ]
            ]
        );
    }
}
