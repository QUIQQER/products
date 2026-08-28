<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\ListProductTypes
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\MCP\AbstractTool;
use QUI\ERP\Products\Product\Types\AbstractType;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\ProductTypes;
use Throwable;

class ListProductTypes extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string | null $lang = null): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();
                    $Locale = self::getLocale($lang);
                    $types = [];

                    foreach (ProductTypes::getInstance()->getProductTypes() as $type) {
                        if (!is_string($type)) {
                            continue;
                        }

                        $type = ltrim($type, '\\');

                        if (!class_exists($type) || !is_a($type, AbstractType::class, true)) {
                            continue;
                        }

                        $types[] = [
                            'type' => $type,
                            'title' => $type::getTypeTitle($Locale),
                            'description' => $type::getTypeDescription($Locale),
                            'selectable' => $type::isTypeSelectable(),
                            'variantParent' => is_a($type, VariantParent::class, true),
                            'variantChild' => is_a($type, VariantChild::class, true)
                        ];
                    }

                    usort($types, static function (array $a, array $b): int {
                        return strcasecmp($a['title'], $b['title']);
                    });

                    return ['count' => count($types), 'productTypes' => $types];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_product_types_list',
            description: 'Lists registered product types and identifies selectable, variant-parent and variant-child types.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'lang' => ['type' => 'string', 'description' => 'Language used for localized type metadata.']
                ]
            ]
        );
    }
}
