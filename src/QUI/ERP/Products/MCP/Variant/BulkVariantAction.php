<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\BulkVariantAction
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class BulkVariantAction extends AbstractVariantTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, array $variantIds, string $action): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    if (!in_array($action, ['activate', 'deactivate', 'delete'], true)) {
                        throw new \QUI\Exception('Unknown variant action: ' . $action, 400);
                    }

                    if ($action === 'delete') {
                        self::checkPermission('product.delete');
                    } else {
                        self::checkPermission('product.activate');
                    }

                    $Parent = self::getVariantParent($productId, true);
                    $variantIds = array_values(array_unique(array_map('intval', $variantIds)));

                    if ($variantIds === []) {
                        throw new \QUI\Exception('At least one variant ID is required.', 400);
                    }

                    if ($action === 'delete' && in_array($Parent->getDefaultVariantId(), $variantIds, true)) {
                        throw new \QUI\Exception(
                            'Clear or change the default variant before deleting it.',
                            400
                        );
                    }

                    $Variants = [];

                    foreach ($variantIds as $variantId) {
                        $Variants[$variantId] = self::getVariantChild($variantId, $Parent);
                    }

                    $processedIds = [];
                    $failures = [];

                    foreach ($Variants as $variantId => $Variant) {
                        try {
                            match ($action) {
                                'activate' => $Variant->activate(Server::getRequestUser()),
                                'deactivate' => $Variant->deactivate(Server::getRequestUser()),
                                'delete' => $Variant->delete()
                            };
                            $processedIds[] = $variantId;
                        } catch (Throwable $Exception) {
                            $failures[] = [
                                'variantId' => $variantId,
                                'message' => $Exception->getMessage(),
                                'code' => $Exception->getCode()
                            ];
                        }
                    }

                    return [
                        'variantParentId' => $Parent->getId(),
                        'action' => $action,
                        'processedVariantIds' => $processedIds,
                        'failures' => $failures
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_bulk_action',
            description: 'Activates, deactivates or permanently deletes multiple variants of one parent.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId', 'variantIds', 'action'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'variantIds' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'maxItems' => 100,
                        'uniqueItems' => true,
                        'items' => ['type' => 'integer', 'minimum' => 1]
                    ],
                    'action' => ['type' => 'string', 'enum' => ['activate', 'deactivate', 'delete']]
                ]
            ]
        );
    }
}
