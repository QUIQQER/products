<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Category\CreateCategory
 */

namespace QUI\ERP\Products\MCP\Category;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Categories;
use Throwable;

class CreateCategory extends AbstractCategoryTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                array $translations,
                int | null $parentId = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('category.create');
                    self::validateTranslations($translations);
                    $parentId ??= 0;
                    self::getCategory($parentId);
                    $currentLang = $lang ?: QUI::getLocale()->getCurrent();
                    $title = (string)($translations[$currentLang]['title'] ?? '');
                    $Category = Categories::createCategory($parentId, $title);
                    self::updateTranslations($Category, $translations);

                    return self::parseCategory($Category, $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_categories_create',
            description: 'Creates a product category below an existing parent category.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['translations'],
                'properties' => [
                    'translations' => self::translationsSchema(),
                    'parentId' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
