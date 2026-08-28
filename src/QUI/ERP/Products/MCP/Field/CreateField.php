<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\CreateField
 */

namespace QUI\ERP\Products\MCP\Field;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Fields;
use Throwable;

class CreateField extends AbstractFieldTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $type,
                array $translations,
                int | null $fieldId = null,
                string | null $name = null,
                array | null $options = null,
                bool | null $public = null,
                bool | null $required = null,
                bool | null $showInDetails = null,
                int | null $priority = null,
                array | null $prefixes = null,
                array | null $suffixes = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('field.create');
                    self::validateTranslations($translations, true);

                    if ($fieldId !== null && $fieldId < 1000) {
                        throw new QUI\Exception('Custom field IDs must be at least 1000.', 400);
                    }

                    if (Fields::getFieldTypeData($type) === []) {
                        throw new QUI\Exception('Unknown field type: ' . $type, 400);
                    }

                    if ($prefixes !== null) {
                        self::validateLocalizedValues($prefixes);
                    }

                    if ($suffixes !== null) {
                        self::validateLocalizedValues($suffixes);
                    }

                    $translationAttributes = self::buildTranslationAttributes($translations);
                    $attributes = [
                        'type' => $type,
                        'name' => $name ?? '',
                        'publicField' => (int)($public ?? true),
                        'requiredField' => (int)($required ?? false),
                        'showInDetails' => (int)($showInDetails ?? false),
                        'priority' => $priority ?? 0,
                        ...$translationAttributes
                    ];

                    if ($fieldId !== null) {
                        $attributes['id'] = $fieldId;
                    }

                    if ($options !== null) {
                        $attributes['options'] = $options;
                    }

                    if ($prefixes !== null) {
                        $attributes['prefix'] = json_encode($prefixes, JSON_THROW_ON_ERROR);
                    }

                    if ($suffixes !== null) {
                        $attributes['suffix'] = json_encode($suffixes, JSON_THROW_ON_ERROR);
                    }

                    $Field = Fields::createField($attributes);

                    return self::parseField($Field, $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_fields_create',
            description: 'Creates a custom product-field definition from a registered field type.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['type', 'translations'],
                'properties' => [
                    'type' => ['type' => 'string', 'description' => 'Registered field type name.'],
                    'translations' => self::translationsSchema(),
                    'fieldId' => [
                        'type' => 'integer',
                        'minimum' => 1000,
                        'description' => 'Optional stable custom field ID; generated when omitted.'
                    ],
                    'name' => ['type' => 'string', 'description' => 'Internal working name.'],
                    'options' => ['type' => 'object'],
                    'public' => ['type' => 'boolean', 'default' => true],
                    'required' => ['type' => 'boolean', 'default' => false],
                    'showInDetails' => ['type' => 'boolean', 'default' => false],
                    'priority' => ['type' => 'integer', 'default' => 0],
                    'prefixes' => self::localizedValuesSchema(),
                    'suffixes' => self::localizedValuesSchema(),
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
