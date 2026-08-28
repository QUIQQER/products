<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\GetField
 */

namespace QUI\ERP\Products\MCP\Field;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetField extends AbstractFieldTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $fieldId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    return self::parseField(self::getField($fieldId), $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_fields_get',
            description: 'Returns one product-field definition including options, translations and usage count.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['fieldId'],
                'properties' => [
                    'fieldId' => ['type' => 'integer', 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Language used for localized values.']
                ]
            ]
        );
    }
}
