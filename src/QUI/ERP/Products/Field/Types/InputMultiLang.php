<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\InputMultiLang
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class InputMultiLang
 * @package QUI\ERP\Products\Field
 */
class InputMultiLang extends QUI\ERP\Products\Field\Field
{
    public function getBackendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    public function getFrontendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
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
     */
    public function setValue($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        parent::setValue($value);
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
        if (empty($value)) {
            return;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                )
            ));
        }

        $keys = array_keys($value);

        foreach ($keys as $lang) {
            if (!is_string($lang) || strlen($lang) != 2) {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType' => $this->getType()
                    )
                ));
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
        $languages = QUI\Translator::getAvailableLanguages();

        if (!is_array($value)) {
            return array_fill_keys($languages, '');
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

        return $result;
    }
}
