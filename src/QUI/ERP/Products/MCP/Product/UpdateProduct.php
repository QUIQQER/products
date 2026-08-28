<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\UpdateProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class UpdateProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $productId,
                array | null $fields = null,
                array | null $categoryIds = null,
                int | null $mainCategoryId = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.edit');
                    $Product = self::getProduct($productId);

                    if ($categoryIds !== null) {
                        self::updateCategories($Product, $categoryIds, $mainCategoryId);
                    } elseif ($mainCategoryId !== null) {
                        $categoryIds = [];

                        foreach ($Product->getCategories() as $Category) {
                            $categoryIds[] = $Category->getId();
                        }

                        if (!in_array($mainCategoryId, $categoryIds, true)) {
                            throw new QUI\Exception('The main category must already be assigned to the product.', 400);
                        }

                        $Product->setMainCategory($mainCategoryId);
                    }

                    if ($fields !== null) {
                        self::updateFields($Product, $fields);
                    }

                    $Product->save(Server::getRequestUser());

                    return self::parseProduct($Product, $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_update',
            description: 'Updates fields and category assignments of an existing product.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Product ID.'],
                    'fields' => self::fieldDataSchema(),
                    'categoryIds' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer', 'minimum' => 0],
                        'minItems' => 1,
                        'uniqueItems' => true,
                        'description' => 'Complete replacement of assigned category IDs.'
                    ],
                    'mainCategoryId' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'description' => 'Main category ID; must be assigned to the product.'
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
