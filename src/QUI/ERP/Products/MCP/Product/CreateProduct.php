<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\CreateProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\MCP\AbstractTool;
use QUI\ERP\Products\Product\Types\Product;
use QUI\ERP\Products\Utils\ProductTypes;
use Throwable;

class CreateProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                array $categoryIds = [],
                array $fields = [],
                string | null $productType = null,
                int | null $parentId = null,
                int | null $mainCategoryId = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    self::checkPermission('product.create');

                    if ($mainCategoryId !== null && $categoryIds === []) {
                        $categoryIds = [$mainCategoryId];
                    }

                    self::validateMainCategory($categoryIds, $mainCategoryId);

                    if ($mainCategoryId !== null) {
                        $categoryIds = array_values(array_unique([
                            $mainCategoryId,
                            ...$categoryIds
                        ]));
                    }

                    $productType = ltrim($productType ?: Product::class, '\\');

                    if (!ProductTypes::getInstance()->exists($productType)) {
                        throw new QUI\Exception('Unknown product type: ' . $productType, 400);
                    }

                    $Product = Products::createProduct(
                        $categoryIds,
                        self::createFields($fields),
                        $productType,
                        $parentId
                    );

                    return self::parseProduct($Product, $lang, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_create',
            description: 'Creates a product using existing categories, product fields and a registered product type.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'categoryIds' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer', 'minimum' => 0],
                        'uniqueItems' => true,
                        'description' => 'Existing category IDs. The main product category is used when omitted.'
                    ],
                    'fields' => self::fieldDataSchema(),
                    'productType' => [
                        'type' => 'string',
                        'description' => 'Registered product type class. Defaults to the standard product type.'
                    ],
                    'parentId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Parent product ID for variants.'],
                    'mainCategoryId' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'description' => 'Main category ID; must also occur in categoryIds.'
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.']
                ]
            ]
        );
    }
}
