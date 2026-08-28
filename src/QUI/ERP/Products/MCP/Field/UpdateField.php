<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\UpdateField
 */

namespace QUI\ERP\Products\MCP\Field;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateField extends AbstractFieldTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $fieldId,
                array | null $changes = null,
                array | null $translations = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('field.edit');

                    if ($changes === null && $translations === null) {
                        throw new QUI\Exception('Either changes or translations must be provided.', 400);
                    }

                    if ($changes !== null) {
                        self::validateChanges($changes);
                    }

                    if ($translations !== null) {
                        self::validateTranslations($translations);
                    }

                    $Field = self::getField($fieldId);

                    if ($changes !== null) {
                        self::applyChanges($Field, $changes);
                    }

                    if ($translations !== null) {
                        self::updateTranslations($Field, $translations);
                    }

                    return self::parseField(self::getField($fieldId), $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_fields_update',
            description: 'Updates a product-field definition without changing its field type or system status.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['fieldId'],
                'properties' => [
                    'fieldId' => ['type' => 'integer', 'minimum' => 1],
                    'changes' => self::changesSchema(),
                    'translations' => self::translationsSchema(),
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
