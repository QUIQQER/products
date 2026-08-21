<?php

/**
 * This file contains QUI\ERP\Products\Utils\DataLayer
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Product;
use QUI\ERP\Products\Product\Types\VariantChild;

/**
 * Creates product-owned GA4 ecommerce data.
 */
class DataLayer
{
    /**
     * @param QUI\Locale|null $Locale
     * @return array<string, mixed>
     */
    public static function parseProduct(Product $Product, $Locale = null): array
    {
        $manufacturer = '';
        $variant = '';
        $manufacturerField = $Product->getField(Fields::FIELD_MANUFACTURER)->getValue();

        if (!empty($manufacturerField) && isset($manufacturerField[0])) {
            try {
                $manufacturer = QUI::getUsers()->get($manufacturerField[0])->getName();
            } catch (QUI\Exception) {
            }
        }

        if ($Product instanceof VariantChild) {
            $variant = $Product->generateVariantHash();
        }

        $item = [
            'item_id' => $Product->getField(Fields::FIELD_PRODUCT_NO)->getValue(),
            'item_name' => $Product->getTitle($Locale),
            'item_brand' => $manufacturer,
            'item_category' => $Product->getCategory()?->getTitle($Locale) ?? '',
            'item_variant' => $variant,
            'price' => $Product->getPrice()->getPrice()
        ];
        $categoryNumber = 2;

        foreach ($Product->getCategories() as $Category) {
            $item['item_category' . $categoryNumber] = $Category->getTitle($Locale);
            $categoryNumber++;
        }

        return $item;
    }

    /**
     * @param QUI\Locale|null $Locale
     * @return array<string, mixed>
     */
    public static function parseProductEvent(Product $Product, $Locale = null): array
    {
        $Price = $Product->getPrice();
        $item = self::parseProduct($Product, $Locale);
        $item['quantity'] = 1;

        return [
            'currency' => $Price->getCurrency()->getCode(),
            'value' => $Price->getPrice(),
            'items' => [$item]
        ];
    }

    /**
     * @param array<int, int|string> $productIds
     * @param QUI\Locale|null $Locale
     * @return array<int, array<string, mixed>>
     */
    public static function parseProductItems(array $productIds, $Locale = null, int $startIndex = 0): array
    {
        $items = [];

        foreach (array_slice($productIds, 0, 100) as $position => $productId) {
            try {
                $Product = Products::getProduct((int)$productId);
            } catch (QUI\Exception) {
                continue;
            }

            $item = self::parseProduct($Product, $Locale);
            $item['index'] = $startIndex + $position;
            $items[] = $item;
        }

        return $items;
    }
}
