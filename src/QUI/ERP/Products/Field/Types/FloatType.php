<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\FloatType
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class FloatType extends QUI\ERP\Products\Field\Field
{
    public function getBackendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => '',
            'suffix' => '',
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
        return 'package/quiqqer/products/bin/controls/fields/types/FloatType';
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
        // TODO: Implement validate() method.
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup($value)
    {
        $value = (string)$value;

        if (trim($value) === '') {
            return 0.0;
        }

        if (mb_strpos($value, ',') !== false) {
            $value = preg_replace('#[^\d,]#i', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return round((float)$value, 5);
    }
}
