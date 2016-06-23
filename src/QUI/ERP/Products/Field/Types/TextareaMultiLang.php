<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\TextareaMultiLang
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class TextareaMultiLang
 * @package QUI\ERP\Products\Field
 */
class TextareaMultiLang extends QUI\ERP\Products\Field\Field
{
    /**
     * @var int
     */
    protected $searchDataType = Search::SEARCHDATATYPE_TEXT;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/TextareaMultiLang';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/TextareaMultiLangSettings';
    }

    /**
     * Return the field value by a locale language
     *
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
            if (is_string($value)) {
                return $value;
            }

            if (isset($value[$current])) {
                return $value[$current];
            }
        } catch (QUI\Exception $Exception) {
        }

        return $value;
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        if (!is_string($value) && !is_array($value)) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    )
                ));
            }
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    )
                ));
            }
        }

        if (empty($value)) {
            return;
        }

        // 2 sign locales to 5 signs locales
        foreach ($value as $lang => $data) {
            if (strlen($lang) === 2) {
                $locale = QUI::getLocale()->parseLangToLocaleCode($lang);

                $value[$locale] = $value[$lang];
                unset($value[$lang]);
            }
        }

        $keys = array_keys($value);

        foreach ($keys as $lang) {
            if (!is_string($lang) || strlen($lang) != 2) {
                throw new QUI\ERP\Products\Field\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    )
                ));
            }
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return string
     */
    public function cleanup($value)
    {
        if (empty($value)) {
            return null;
        }

        // 2 sign locales to 5 signs locales
        foreach ($value as $lang => $data) {
            if (strlen($lang) === 2) {
                $locale = QUI::getLocale()->parseLangToLocaleCode($lang);

                $value[$locale] = $value[$lang];
                unset($value[$lang]);
            }
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

        $result = array();

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
        return array(
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE
        );
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
