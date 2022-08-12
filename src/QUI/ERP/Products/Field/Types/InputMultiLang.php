<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\InputMultiLang
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class InputMultiLang
 * @package QUI\ERP\Products\Field
 */
class InputMultiLang extends QUI\ERP\Products\Field\Field
{
    protected $columnType     = 'TEXT';
    protected $searchDataType = Search::SEARCHDATATYPE_TEXT;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return MultilangFrontendView
     */
    public function getFrontendView()
    {
        return new MultilangFrontendView($this->getFieldDataForView());
    }

    /**
     * Return the field value by a locale language
     *
     * @param bool|QUI\Locale $Locale
     * @return mixed
     */
    public function getValueByLocale($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $value   = $this->getValue();

        try {
            if (\is_string($value)) {
                return $value;
            }

            // standard validate
            if (isset($value[$current])) {
                return $value[$current];
            }

            // lang validate
            if (\strpos($current, '_') !== false) {
                $current = \explode('_', $current);
                $current = $current[0];

                if (isset($value[$current])) {
                    return $value[$current];
                }
            }

            $current = \mb_strtolower($current).'_'.\mb_strtoupper($current);

            if (isset($value[$current])) {
                return $value[$current];
            }
        } catch (QUI\Exception $Exception) {
        }

        if (\is_array($value)) {
            \reset($value);

            return \current($value);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/InputMultiLang';
    }

    /**
     * @param mixed $value
     *
     * @throws QUI\Exception
     */
    public function setValue($value)
    {
        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        parent::setValue($value);
    }

    /**
     * @param string $langValue
     * @param QUI\Locale|null $Locale
     *
     * @return void
     * @throws QUI\Exception
     */
    public function setValueByLocale(string $langValue, ?QUI\Locale $Locale = null): void
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $lang  = $Locale->getCurrent();
        $value = $this->getValue();

        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        $value[$lang] = $langValue;

        $this->setValue($value);
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        if (!\is_string($value) && !\is_array($value)) {
            if (\json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    ]
                ]);
            }
        }

        if (\is_string($value)) {
            $value = \json_decode($value, true);

            if (\json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    ]
                ]);
            }
        }

        if (empty($value)) {
            return;
        }

        $keys = \array_keys($value);

        foreach ($keys as $lang) {
            if (!\is_string($lang) || \strlen($lang) != 2) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    ]
                ]);
            }
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return array
     */
    public function cleanup($value)
    {
        try {
            $languages = QUI\Translator::getAvailableLanguages();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return [];
        }


        if (!\is_array($value)) {
            return \array_fill_keys($languages, '');
        }

        $result = [];

        foreach ($value as $key => $val) {
            if (!\is_string($key) || \strlen($key) != 2) {
                continue;
            }

            $result[$key] = $val;
        }

        foreach ($languages as $lang) {
            if (!isset($result[$lang])) {
                $result[$lang] = '';
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (empty($this->value)) {
            return true;
        }

        foreach ($this->value as $l => $v) {
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
    public function getSearchTypes()
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
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_TEXT;
    }
}
