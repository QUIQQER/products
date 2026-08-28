<?php

/**
 * This file contains \QUI\ERP\Products\MCP\AbstractTool
 */

namespace QUI\ERP\Products\MCP;

use QUI;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Field\Types\Price as PriceField;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\AbstractType;
use QUI\Locale;
use QUI\MCP\ToolInterface;
use QUI\Permissions\Permission;

use function array_key_exists;
use function class_exists;
use function is_scalar;
use function max;
use function min;

abstract class AbstractTool implements ToolInterface
{
    public const PRODUCTS_MCP_PERMISSION = 'quiqqer.products.mcp';

    protected static function checkProductsMcpPermission(): void
    {
        Permission::checkPermission(
            self::PRODUCTS_MCP_PERMISSION,
            Server::getRequestUser()
        );
    }

    protected static function checkPermission(string $permission): void
    {
        Permission::checkPermission($permission, Server::getRequestUser());
    }

    protected static function getProduct(int $productId): AbstractType
    {
        return Products::getProduct($productId);
    }

    protected static function getLocale(?string $lang = null): Locale
    {
        $Locale = new Locale();
        $Locale->setCurrent($lang ?: Products::getLocale()->getCurrent());

        return $Locale;
    }

    protected static function sanitizeLimit(?int $limit): int
    {
        if (empty($limit)) {
            return 20;
        }

        return (int)min(100, max(1, $limit));
    }

    protected static function sanitizeOffset(?int $offset): int
    {
        return (int)max(0, $offset ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseProduct(
        AbstractType $Product,
        ?string $lang = null,
        bool $includeFields = false,
        bool $includePrices = false
    ): array {
        $Locale = self::getLocale($lang);
        $type = (string)$Product->getAttribute('type');
        $categoryIds = [];

        foreach ($Product->getCategories() as $Category) {
            $categoryIds[] = $Category->getId();
        }

        $MainCategory = $Product->getCategory();
        $productNo = $Product->getFieldValueByLocale(Fields::FIELD_PRODUCT_NO, $Locale);
        $result = [
            'id' => $Product->getId(),
            'active' => $Product->isActive(),
            'type' => $type,
            'typeTitle' => class_exists($type) && is_a($type, AbstractType::class, true)
                ? $type::getTypeTitle($Locale)
                : '',
            'parentId' => ($parentId = (int)$Product->getAttribute('parent')) > 0 ? $parentId : null,
            'productNo' => is_scalar($productNo) ? (string)$productNo : '',
            'title' => $Product->getTitle($Locale),
            'description' => $Product->getDescription($Locale),
            'content' => $Product->getContent($Locale),
            'mainCategoryId' => $MainCategory?->getId(),
            'categoryIds' => $categoryIds,
            'image' => null,
            'createdAt' => $Product->getAttribute('c_date') ?: null,
            'updatedAt' => $Product->getAttribute('e_date') ?: null
        ];

        try {
            $result['image'] = $Product->getImage()->getUrl(true);
        } catch (QUI\Exception) {
        }

        if ($includeFields) {
            $result['fields'] = self::parseFields($Product, $Locale, $includePrices);
        }

        if ($includePrices) {
            $Price = $Product->getPrice(Server::getRequestUser());
            $result['price'] = [
                'value' => $Price->value(),
                'currency' => $Price->getCurrency()->getCode()
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function parseFields(AbstractType $Product, Locale $Locale, bool $includePrices): array
    {
        $result = [];

        foreach ($Product->getFields() as $Field) {
            if (!$includePrices && $Field instanceof PriceField) {
                continue;
            }

            $result[] = [
                'id' => $Field->getId(),
                'type' => $Field->getType(),
                'title' => $Field->getTitle($Locale),
                'value' => $Field->getValueByLocale($Locale)
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $fieldData
     * @return array<int, Field>
     */
    protected static function createFields(array $fieldData): array
    {
        $result = [];

        foreach ($fieldData as $entry) {
            if (!array_key_exists('id', $entry) || !array_key_exists('value', $entry)) {
                throw new QUI\Exception('Each product field requires an id and a value.', 400);
            }

            $Field = clone Fields::getField((int)$entry['id']);
            $Field->setValue($entry['value']);
            $result[] = $Field;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $fieldData
     */
    protected static function updateFields(AbstractType $Product, array $fieldData): void
    {
        foreach ($fieldData as $entry) {
            if (!array_key_exists('id', $entry) || !array_key_exists('value', $entry)) {
                throw new QUI\Exception('Each product field requires an id and a value.', 400);
            }

            $fieldId = (int)$entry['id'];

            if (!$Product->hasField($fieldId)) {
                $Product->addOwnField(Fields::getField($fieldId));
            }

            $Product->getField($fieldId)->setValue($entry['value']);
        }
    }

    /**
     * @param int[] $categoryIds
     */
    protected static function updateCategories(
        AbstractType $Product,
        array $categoryIds,
        ?int $mainCategoryId = null
    ): void {
        $Product->clearCategories();
        $categories = [];

        foreach (array_values(array_unique(array_map('intval', $categoryIds))) as $categoryId) {
            $Category = Categories::getCategory($categoryId);
            $categories[$categoryId] = $Category;
            $Product->addCategory($Category);
        }

        if ($categories === []) {
            throw new QUI\Exception('At least one product category is required.', 400);
        }

        $mainCategoryId ??= (int)array_key_first($categories);

        if (!isset($categories[$mainCategoryId])) {
            throw new QUI\Exception('The main category must be included in categoryIds.', 400);
        }

        $Product->setMainCategory($categories[$mainCategoryId]);
    }

    /**
     * @param int[] $categoryIds
     */
    protected static function validateMainCategory(array $categoryIds, ?int $mainCategoryId): void
    {
        if ($mainCategoryId !== null && !in_array($mainCategoryId, $categoryIds, true)) {
            throw new QUI\Exception('The main category must be included in categoryIds.', 400);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function fieldDataSchema(): array
    {
        return [
            'type' => 'array',
            'description' => 'Existing product field IDs and their new values.',
            'items' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['id', 'value'],
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'value' => [
                        'description' => 'Field value. Language fields accept an object keyed by language code.'
                    ]
                ]
            ]
        ];
    }
}
