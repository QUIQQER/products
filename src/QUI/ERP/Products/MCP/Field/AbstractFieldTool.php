<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Field\AbstractFieldTool
 */

namespace QUI\ERP\Products\MCP\Field;

use QUI;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\MCP\AbstractTool;

abstract class AbstractFieldTool extends AbstractTool
{
    protected static function getField(int $fieldId): Field
    {
        return Fields::getField($fieldId);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseField(
        Field $Field,
        ?string $lang = null,
        bool $includeDetails = false
    ): array {
        $Locale = self::getLocale($lang);
        $result = [
            'id' => $Field->getId(),
            'name' => $Field->getName(),
            'type' => $Field->getType(),
            'title' => $Field->getTitle($Locale),
            'workingTitle' => $Field->getWorkingTitle($Locale),
            'description' => $Field->getDescription($Locale),
            'priority' => (int)$Field->getAttribute('priority'),
            'searchType' => $Field->getSearchType(),
            'searchable' => $Field->isSearchable(),
            'public' => $Field->isPublic(),
            'required' => $Field->isRequired(),
            'standard' => $Field->isStandard(),
            'system' => $Field->isSystem(),
            'custom' => $Field->isCustomField(),
            'showInDetails' => $Field->showInDetails(),
            'defaultValue' => $Field->getDefaultValue(),
            'prefix' => $Field->getPrefix($Locale),
            'suffix' => $Field->getSuffix($Locale)
        ];

        if ($includeDetails) {
            $result['options'] = $Field->getOptions();
            $result['searchTypes'] = $Field->getSearchTypes();
            $result['searchDataType'] = $Field->getSearchDataType();
            $result['columnType'] = $Field->getColumnType();
            $result['unassigned'] = $Field->isUnassigned();
            $result['ownField'] = $Field->isOwnField();
            $result['prefixes'] = self::parseLocalizedAttribute(
                $Field->getAttribute('prefix'),
                $Locale->getCurrent()
            );
            $result['suffixes'] = self::parseLocalizedAttribute(
                $Field->getAttribute('suffix'),
                $Locale->getCurrent()
            );
            $result['translations'] = self::getTranslations($Field);
            $result['productCount'] = count($Field->getProductIds());
        }

        return $result;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected static function getTranslations(Field $Field): array
    {
        $result = [];

        foreach (QUI::availableLanguages() as $lang) {
            $Locale = self::getLocale($lang);
            $result[$lang] = [
                'title' => $Field->getTitle($Locale),
                'workingTitle' => $Field->getWorkingTitle($Locale),
                'description' => $Field->getDescription($Locale)
            ];
        }

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $translations
     */
    protected static function validateTranslations(array $translations, bool $requireTitle = false): void
    {
        $availableLanguages = array_flip(QUI::availableLanguages());
        $hasTitle = false;

        foreach ($translations as $lang => $translation) {
            if (!isset($availableLanguages[$lang])) {
                throw new QUI\Exception('Unknown language: ' . $lang, 400);
            }

            if (!is_array($translation)) {
                throw new QUI\Exception('Each field translation must be an object.', 400);
            }

            if (isset($translation['title']) && trim((string)$translation['title']) !== '') {
                $hasTitle = true;
            }
        }

        if ($requireTitle && !$hasTitle) {
            throw new QUI\Exception('At least one non-empty field title is required.', 400);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $translations
     */
    protected static function updateTranslations(Field $Field, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        self::validateTranslations($translations);
        $titles = [];
        $workingTitles = [];
        $descriptions = [];

        foreach ($translations as $lang => $translation) {
            if (array_key_exists('title', $translation)) {
                $titles[$lang] = (string)$translation['title'];
            }

            if (array_key_exists('workingTitle', $translation)) {
                $workingTitles[$lang] = (string)$translation['workingTitle'];
            }

            if (array_key_exists('description', $translation)) {
                $descriptions[$lang] = (string)$translation['description'];
            }
        }

        Fields::setFieldTranslations($Field->getId(), [
            'titles' => $titles,
            'workingtitles' => $workingTitles,
            'description' => $descriptions
        ]);
        Fields::clearCache();
        QUI::getLocale()->refresh();
    }

    /**
     * @param array<string, mixed> $changes
     */
    protected static function validateChanges(array $changes): void
    {
        foreach (['prefixes', 'suffixes'] as $attribute) {
            if (!isset($changes[$attribute])) {
                continue;
            }

            if (!is_array($changes[$attribute])) {
                throw new QUI\Exception($attribute . ' must be an object keyed by language.', 400);
            }

            self::validateLocalizedValues($changes[$attribute]);
        }
    }

    /**
     * @param array<string, mixed> $changes
     */
    protected static function applyChanges(Field $Field, array $changes): void
    {
        if (array_key_exists('options', $changes)) {
            if (!is_array($changes['options'])) {
                throw new QUI\Exception('options must be an object.', 400);
            }

            $Field->setOptions($changes['options']);
        }

        if (array_key_exists('defaultValue', $changes)) {
            if ($changes['defaultValue'] === null) {
                $Field->clearDefaultValue();
            } else {
                $Field->setDefaultValue($changes['defaultValue']);
            }
        }

        $attributeMap = [
            'name' => 'name',
            'searchType' => 'search_type',
            'priority' => 'priority',
            'public' => 'publicField',
            'required' => 'requiredField',
            'showInDetails' => 'showInDetails'
        ];

        foreach ($attributeMap as $inputAttribute => $fieldAttribute) {
            if (array_key_exists($inputAttribute, $changes)) {
                $Field->setAttribute($fieldAttribute, $changes[$inputAttribute]);
            }
        }

        if (array_key_exists('prefixes', $changes)) {
            $Field->setAttribute('prefix', json_encode($changes['prefixes'], JSON_THROW_ON_ERROR));
        }

        if (array_key_exists('suffixes', $changes)) {
            $Field->setAttribute('suffix', json_encode($changes['suffixes'], JSON_THROW_ON_ERROR));
        }

        $Field->save();
    }

    /**
     * @param array<string, mixed> $values
     */
    protected static function validateLocalizedValues(array $values): void
    {
        $availableLanguages = array_flip(QUI::availableLanguages());

        foreach ($values as $lang => $value) {
            if (!isset($availableLanguages[$lang])) {
                throw new QUI\Exception('Unknown language: ' . $lang, 400);
            }

            if (!is_string($value)) {
                throw new QUI\Exception('Localized field values must be strings.', 400);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected static function parseLocalizedAttribute(mixed $value, string $currentLang): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $value = $decoded;
            } elseif ($value !== '') {
                return [$currentLang => $value];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $lang => $localizedValue) {
            if (is_string($localizedValue)) {
                $result[(string)$lang] = $localizedValue;
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $translation
     * @return array<string, array<string, string>>
     */
    protected static function buildTranslationAttributes(array $translation): array
    {
        $result = [
            'titles' => [],
            'workingtitles' => [],
            'description' => []
        ];

        foreach ($translation as $lang => $entry) {
            if (array_key_exists('title', $entry)) {
                $result['titles'][$lang] = (string)$entry['title'];
            }

            if (array_key_exists('workingTitle', $entry)) {
                $result['workingtitles'][$lang] = (string)$entry['workingTitle'];
            }

            if (array_key_exists('description', $entry)) {
                $result['description'][$lang] = (string)$entry['description'];
            }
        }

        return $result;
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
                    'workingTitle' => ['type' => 'string'],
                    'description' => ['type' => 'string']
                ],
                'minProperties' => 1
            ],
            'minProperties' => 1
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function localizedValuesSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => ['type' => 'string']
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function changesSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'minProperties' => 1,
            'properties' => [
                'name' => ['type' => 'string'],
                'searchType' => ['type' => 'string'],
                'priority' => ['type' => 'integer'],
                'public' => ['type' => 'boolean'],
                'required' => ['type' => 'boolean'],
                'showInDetails' => ['type' => 'boolean'],
                'options' => [
                    'type' => 'object',
                    'description' => 'Options merged into the existing option map.'
                ],
                'defaultValue' => ['description' => 'New default value; null clears it.'],
                'prefixes' => self::localizedValuesSchema(),
                'suffixes' => self::localizedValuesSchema()
            ]
        ];
    }
}
