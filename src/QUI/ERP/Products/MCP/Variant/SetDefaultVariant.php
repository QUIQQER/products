<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\SetDefaultVariant
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class SetDefaultVariant extends AbstractVariantTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $productId, int | null $variantId = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.edit');
                    $Parent = self::getVariantParent($productId, true);

                    if (empty($variantId)) {
                        $Parent->unsetDefaultVariant();
                    } else {
                        if (!$Parent->hasVariantId($variantId)) {
                            throw new QUI\Exception(
                                'Product ' . $variantId . ' is not a variant of product ' . $Parent->getId() . '.',
                                400
                            );
                        }

                        $Parent->setDefaultVariant($variantId);
                    }

                    $Parent->save(Server::getRequestUser());

                    return [
                        'variantParentId' => $Parent->getId(),
                        'defaultVariantId' => $Parent->getDefaultVariantId() ?: null
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_default_set',
            description: 'Sets or clears the default child variant of a variant-parent product.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'variantId' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'default' => 0,
                        'description' => 'Child variant ID; 0 clears the default.'
                    ]
                ]
            ]
        );
    }
}
