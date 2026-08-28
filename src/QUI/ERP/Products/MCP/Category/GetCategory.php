<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Category\GetCategory
 */

namespace QUI\ERP\Products\MCP\Category;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetCategory extends AbstractCategoryTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $categoryId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    return self::parseCategory(self::getCategory($categoryId), $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_categories_get',
            description: 'Returns one product category including hierarchy, counts and assigned product field IDs.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['categoryId'],
                'properties' => [
                    'categoryId' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Category ID.'],
                    'lang' => ['type' => 'string', 'description' => 'Language used for localized values.']
                ]
            ]
        );
    }
}
