<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\DeleteField
 */

namespace QUI\ERP\Products\MCP\Field;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DeleteField extends AbstractFieldTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $fieldId,
                bool | null $deleteSystemField = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('field.delete');
                    $Field = self::getField($fieldId);
                    $result = self::parseField($Field, $lang, true);

                    if ($Field->isSystem()) {
                        if ($deleteSystemField !== true) {
                            throw new QUI\Exception(
                                'Deleting a system field requires deleteSystemField=true.',
                                400
                            );
                        }

                        self::checkPermission('field.delete.systemfield');
                        $Field->deleteSystemField();
                    } else {
                        $Field->delete();
                    }

                    return ['deleted' => true, 'field' => $result];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_fields_delete',
            description: 'Permanently deletes a product field. System fields require an explicit flag and extra right.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['fieldId'],
                'properties' => [
                    'fieldId' => ['type' => 'integer', 'minimum' => 1],
                    'deleteSystemField' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Explicitly allow deletion of a system field. Requires field.delete.systemfield.'
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned field data.']
                ]
            ]
        );
    }
}
