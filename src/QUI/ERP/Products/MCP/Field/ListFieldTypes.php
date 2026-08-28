<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\ListFieldTypes
 */

namespace QUI\ERP\Products\MCP\Field;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Fields;
use Throwable;

class ListFieldTypes extends AbstractFieldTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    $Locale = self::getLocale($lang);
                    $result = [];

                    foreach (Fields::getFieldTypes() as $typeData) {
                        if (!is_array($typeData) || empty($typeData['name'])) {
                            continue;
                        }

                        $entry = [
                            'name' => (string)$typeData['name'],
                            'class' => (string)($typeData['src'] ?? ''),
                            'plugin' => (string)($typeData['plugin'] ?? ''),
                            'category' => $typeData['category'] ?? 0,
                            'title' => self::translateReference($typeData['locale'] ?? null, $Locale),
                            'help' => self::translateReference($typeData['help'] ?? null, $Locale),
                            'searchTypes' => [],
                            'defaultSearchType' => null
                        ];

                        $class = ltrim($entry['class'], '\\');

                        if (class_exists($class) && is_a($class, Field::class, true)) {
                            try {
                                $Field = new $class(0, ['public' => true]);
                                $entry['searchTypes'] = $Field->getSearchTypes();
                                $entry['defaultSearchType'] = $Field->getDefaultSearchType();
                            } catch (Throwable) {
                            }
                        }

                        $result[] = $entry;
                    }

                    usort($result, static function (array $a, array $b): int {
                        return strcasecmp($a['title'] ?: $a['name'], $b['title'] ?: $b['name']);
                    });

                    return ['count' => count($result), 'fieldTypes' => $result];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_field_types_list',
            description: 'Lists registered product-field types and their search capabilities.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'lang' => ['type' => 'string', 'description' => 'Language used for localized type metadata.']
                ]
            ]
        );
    }

    protected static function translateReference(mixed $reference, QUI\Locale $Locale): string
    {
        if (!is_array($reference) || !isset($reference[0], $reference[1])) {
            return '';
        }

        return $Locale->get((string)$reference[0], (string)$reference[1]);
    }
}
