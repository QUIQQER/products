<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\InputMultiLang
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class InputMultiLang
 * @package QUI\ERP\Products\Field
 */
class InputMultiLang extends QUI\ERP\Products\Field\Field
{
    public function getBackendView()
    {
        // TODO: Implement getBackendView() method.
    }

    public function getFrontendView()
    {
        // TODO: Implement getFrontendView() method.
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
        if (!is_array($value)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.inputMultiLang.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle()
                )
            ));
        }

        $keys = array_keys($value);

        foreach ($keys as $lang) {
            if (!is_string($lang) || strlen($lang) != 2) {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.field.inputMultiLang.invalid',
                    array(
                        'fieldId' => $this->getId()
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
