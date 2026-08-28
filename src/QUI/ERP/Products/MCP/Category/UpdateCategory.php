<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Category\UpdateCategory
 */

namespace QUI\ERP\Products\MCP\Category;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateCategory extends AbstractCategoryTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $categoryId,
                array | null $translations = null,
                int | null $parentId = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('category.edit');

                    if ($categoryId === 0) {
                        throw new QUI\Exception('The virtual all-products category cannot be edited.', 400);
                    }

                    if ($translations !== null) {
                        self::validateTranslations($translations);
                    }

                    $Category = self::getCategory($categoryId);

                    if ($parentId !== null && $parentId !== $Category->getParentId()) {
                        self::validateParent($categoryId, $parentId);
                        $Category->setParentId($parentId);
                        $Category->save(Server::getRequestUser());
                    }

                    if ($translations !== null) {
                        self::updateTranslations($Category, $translations);
                    }

                    return self::parseCategory(self::getCategory($categoryId), $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_categories_update',
            description: 'Updates product-category translations or moves it below another category.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['categoryId'],
                'properties' => [
                    'categoryId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Category ID.'],
                    'translations' => self::translationsSchema(),
                    'parentId' => ['type' => 'integer', 'minimum' => 0],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
