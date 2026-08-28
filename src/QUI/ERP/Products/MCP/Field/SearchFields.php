<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\SearchFields
 */

namespace QUI\ERP\Products\MCP\Field;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Fields;
use Throwable;

class SearchFields extends AbstractFieldTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string | null $query = null,
                string | null $type = null,
                bool | null $system = null,
                bool | null $standard = null,
                bool | null $public = null,
                bool | null $searchable = null,
                string | null $lang = null,
                int | null $limit = null,
                int | null $offset = null,
                string | null $sortBy = null,
                string | null $sortDirection = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    return self::search(
                        $query,
                        $type,
                        $system,
                        $standard,
                        $public,
                        $searchable,
                        $lang,
                        $limit,
                        $offset,
                        $sortBy,
                        $sortDirection
                    );
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_fields_search',
            description: 'Searches product-field definitions by ID, name, type or localized text.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'query' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'description' => 'Exact registered field type name.'],
                    'system' => ['type' => 'boolean'],
                    'standard' => ['type' => 'boolean'],
                    'public' => ['type' => 'boolean'],
                    'searchable' => ['type' => 'boolean'],
                    'lang' => ['type' => 'string', 'description' => 'Language used for localized values.'],
                    'limit' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'default' => 0, 'minimum' => 0],
                    'sortBy' => [
                        'type' => 'string',
                        'enum' => ['id', 'name', 'title', 'type', 'priority'],
                        'default' => 'id'
                    ],
                    'sortDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'ASC']
                ]
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function search(
        ?string $query,
        ?string $type,
        ?bool $system,
        ?bool $standard,
        ?bool $public,
        ?bool $searchable,
        ?string $lang,
        ?int $limit,
        ?int $offset,
        ?string $sortBy,
        ?string $sortDirection
    ): array {
        $fields = [];

        foreach (Fields::getFields() as $Field) {
            if (!$Field instanceof Field) {
                continue;
            }

            if ($type !== null && $type !== '' && $Field->getType() !== $type) {
                continue;
            }

            if ($system !== null && $Field->isSystem() !== $system) {
                continue;
            }

            if ($standard !== null && $Field->isStandard() !== $standard) {
                continue;
            }

            if ($public !== null && $Field->isPublic() !== $public) {
                continue;
            }

            if ($searchable !== null && $Field->isSearchable() !== $searchable) {
                continue;
            }

            $entry = self::parseField($Field, $lang);

            if (!self::matchesQuery($entry, $query)) {
                continue;
            }

            $fields[] = $entry;
        }

        $sortBy = in_array($sortBy, ['id', 'name', 'title', 'type', 'priority'], true) ? $sortBy : 'id';
        $direction = $sortDirection === 'DESC' ? -1 : 1;
        usort($fields, static function (array $a, array $b) use ($sortBy, $direction): int {
            if ($sortBy === 'id' || $sortBy === 'priority') {
                return ((int)$a[$sortBy] <=> (int)$b[$sortBy]) * $direction;
            }

            return strcasecmp((string)$a[$sortBy], (string)$b[$sortBy]) * $direction;
        });

        $count = count($fields);
        $limit = self::sanitizeLimit($limit);
        $offset = self::sanitizeOffset($offset);

        return [
            'count' => $count,
            'limit' => $limit,
            'offset' => $offset,
            'fields' => array_slice($fields, $offset, $limit)
        ];
    }

    /**
     * @param array<string, mixed> $field
     */
    protected static function matchesQuery(array $field, ?string $query): bool
    {
        if ($query === null || trim($query) === '') {
            return true;
        }

        $query = trim($query);

        foreach (['id', 'name', 'type', 'title', 'workingTitle', 'description'] as $attribute) {
            if (stripos((string)$field[$attribute], $query) !== false) {
                return true;
            }
        }

        return false;
    }
}
