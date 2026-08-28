<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\GetProduct
 */

namespace QUI\ERP\Products\MCP\Product;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use Throwable;

class GetProduct extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $productId,
                string | null $lang = null,
                bool | null $includePrices = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    if ($includePrices === true) {
                        self::checkPermission('product.view.prices');
                    }

                    return self::parseProduct(
                        self::getProduct($productId),
                        $lang,
                        true,
                        $includePrices === true
                    );
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_get',
            description: 'Returns one product including its existing product fields and categories.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Product ID.'],
                    'lang' => ['type' => 'string', 'description' => 'Language used for localized values.'],
                    'includePrices' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Include price fields and the calculated product price. Requires product.view.prices.'
                    ]
                ]
            ]
        );
    }
}
