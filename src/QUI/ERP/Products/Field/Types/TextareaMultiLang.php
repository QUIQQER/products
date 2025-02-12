<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\TextareaMultiLang
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;
use QUI\Exception;
use QUI\Locale;

use function array_fill_keys;
use function array_keys;
use function is_array;
use function is_string;
use function json_decode;
use function json_last_error;
use function strlen;

/**
 * Class TextareaMultiLang
 * @package QUI\ERP\Products\Field
 */
class TextareaMultiLang extends QUI\ERP\Products\Field\Field
{
    protected int|bool $searchDataType = Search::SEARCHDATATYPE_TEXT;

    public function getBackendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    public function getFrontendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/TextareaMultiLang';
    }

    /**
     * Return the field value by a locale language
     */
    public function getValueByLocale(null | QUI\Locale $Locale = null): string
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $value = $this->getValue();

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) && isset($value[$current])) {
            return $value[$current];
        }

        return '';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws Exception
     */
    public function validate(mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        if (!is_string($value) && !is_array($value)) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType' => $this->getType()
                    ]
                ]);
            }
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType' => $this->getType()
                    ]
                ]);
            }
        }

        $keys = array_keys($value);

        foreach ($keys as $lang) {
            if (!is_string($lang) || strlen($lang) != 2) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType' => $this->getType()
                    ]
                ]);
            }
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return array|null
     */
    public function cleanup(mixed $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        $languages = QUI\Translator::getAvailableLanguages();

        if (!is_string($value) && !is_array($value)) {
            return array_fill_keys($languages, '');
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return array_fill_keys($languages, '');
            }
        }

        $result = [];

        foreach ($value as $key => $val) {
            if (!is_string($key) || strlen($key) != 2) {
                continue;
            }

            $result[$key] = $val;
        }

        foreach ($languages as $lang) {
            if (!isset($result[$lang])) {
                $result[$lang] = '';
            }
        }

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        if (empty($this->value)) {
            return true;
        }

        foreach ($this->value as $v) {
            if (!empty($v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes(): array
    {
        return [
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE
        ];
    }

    /**
     * Get default search type
     *
     * @return string|null
     */
    public function getDefaultSearchType(): ?string
    {
        return Search::SEARCHTYPE_TEXT;
    }
}
