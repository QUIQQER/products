<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Category\AbstractCategoryTool
 */

namespace QUI\ERP\Products\MCP\Category;

use QUI;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Interfaces\CategoryInterface;
use QUI\ERP\Products\MCP\AbstractTool;

abstract class AbstractCategoryTool extends AbstractTool
{
    protected static function getCategory(int $categoryId): CategoryInterface
    {
        return Categories::getCategory($categoryId);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseCategory(
        CategoryInterface $Category,
        ?string $lang = null,
        bool $includeDetails = false
    ): array {
        $Locale = self::getLocale($lang);
        $parentId = $Category->getParentId();
        $result = [
            'id' => $Category->getId(),
            'parentId' => $parentId === false ? null : $parentId,
            'title' => $Category->getTitle($Locale),
            'description' => $Category->getDescription($Locale),
            'childCount' => $Category->countChildren(),
            'productCount' => $Category->countProducts()
        ];

        if ($includeDetails) {
            $fieldIds = [];

            foreach ($Category->getFields() as $Field) {
                $fieldIds[] = $Field->getId();
            }

            $result['path'] = method_exists($Category, 'getPath') ? $Category->getPath($Locale) : '';
            $result['fieldIds'] = $fieldIds;
        }

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $translations
     */
    protected static function updateTranslations(CategoryInterface $Category, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        self::validateTranslations($translations);
        $titleData = [];
        $descriptionData = [];

        foreach ($translations as $lang => $translation) {
            if (array_key_exists('title', $translation)) {
                $titleData[$lang] = (string)$translation['title'];
            }

            if (array_key_exists('description', $translation)) {
                $descriptionData[$lang] = (string)$translation['description'];
            }
        }

        $categoryId = $Category->getId();

        if ($titleData !== []) {
            QUI\Translator::edit(
                'quiqqer/products',
                'products.category.' . $categoryId . '.title',
                'quiqqer/products',
                $titleData
            );
        }

        if ($descriptionData !== []) {
            QUI\Translator::edit(
                'quiqqer/products',
                'products.category.' . $categoryId . '.description',
                'quiqqer/products',
                $descriptionData
            );
        }

        Categories::clearCache($categoryId);
        QUI::getLocale()->refresh();
    }

    /**
     * @param array<string, array<string, mixed>> $translations
     */
    protected static function validateTranslations(array $translations): void
    {
        $availableLanguages = array_flip(QUI::availableLanguages());

        foreach ($translations as $lang => $translation) {
            if (!isset($availableLanguages[$lang])) {
                throw new QUI\Exception('Unknown language: ' . $lang, 400);
            }

            if (!is_array($translation)) {
                throw new QUI\Exception('Each category translation must be an object.', 400);
            }
        }
    }

    protected static function validateParent(int $categoryId, int $parentId): void
    {
        if ($parentId === $categoryId) {
            throw new QUI\Exception('A category cannot be its own parent.', 400);
        }

        if ($parentId === 0) {
            return;
        }

        $Parent = self::getCategory($parentId);

        while (true) {
            if ($Parent->getId() === $categoryId) {
                throw new QUI\Exception('A category cannot be moved below one of its descendants.', 400);
            }

            if ($Parent->getId() === 0) {
                break;
            }

            $NextParent = $Parent->getParent();

            if (!$NextParent instanceof CategoryInterface) {
                break;
            }

            $Parent = $NextParent;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function translationsSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Translations keyed by an available QUIQQER language code.',
            'additionalProperties' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string']
                ],
                'minProperties' => 1
            ],
            'minProperties' => 1
        ];
    }
}
