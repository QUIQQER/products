<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\UpdateVariantInheritance
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateVariantInheritance extends AbstractVariantTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $productId,
                array | null $editableFieldIds = null,
                array | null $inheritedFieldIds = null,
                bool | null $resetToGlobal = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.edit');

                    if ($resetToGlobal !== true && $editableFieldIds === null && $inheritedFieldIds === null) {
                        throw new QUI\Exception(
                            'Provide editableFieldIds, inheritedFieldIds or resetToGlobal=true.',
                            400
                        );
                    }

                    $Parent = self::getVariantParent($productId, true);

                    if ($resetToGlobal === true) {
                        $Parent->setAttribute('editableVariantFields', false);
                        $Parent->setAttribute('inheritedVariantFields', false);
                    } else {
                        if ($editableFieldIds !== null) {
                            $Parent->setAttribute(
                                'editableVariantFields',
                                self::validateParentFieldIds($Parent, $editableFieldIds)
                            );
                        }

                        if ($inheritedFieldIds !== null) {
                            $Parent->setAttribute(
                                'inheritedVariantFields',
                                self::validateParentFieldIds($Parent, $inheritedFieldIds)
                            );
                        }
                    }

                    $Parent->save(Server::getRequestUser());

                    return self::getInheritanceData($Parent, $lang);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_inheritance_update',
            description: 'Updates product-specific editable and inherited variant fields or resets them to global defaults.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'editableFieldIds' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer', 'minimum' => 1],
                        'uniqueItems' => true
                    ],
                    'inheritedFieldIds' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer', 'minimum' => 1],
                        'uniqueItems' => true
                    ],
                    'resetToGlobal' => ['type' => 'boolean', 'default' => false],
                    'lang' => ['type' => 'string']
                ]
            ]
        );
    }
}
