<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Category\DeleteCategory
 */

namespace QUI\ERP\Products\MCP\Category;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DeleteCategory extends AbstractCategoryTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $categoryId, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('category.delete');

                    if ($categoryId === 0) {
                        throw new QUI\Exception('The virtual all-products category cannot be deleted.', 400);
                    }

                    $Category = self::getCategory($categoryId);
                    $result = self::parseCategory($Category, $lang, true);
                    $Category->delete(Server::getRequestUser());

                    return ['deleted' => true, 'category' => $result];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_categories_delete',
            description: 'Permanently deletes a product category and all of its descendant categories.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['categoryId'],
                'properties' => [
                    'categoryId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Category ID.'],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned category data.']
                ]
            ]
        );
    }
}
