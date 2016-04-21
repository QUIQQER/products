<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\BoolType
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class Price
 * @package QUI\ERP\Products\Field
 */
class BoolType extends QUI\ERP\Products\Field\Field
{
    protected $columnType = 'TINYINT(1)';

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
        return 'package/quiqqer/products/bin/controls/fields/types/BoolType';
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
        switch ($value) {
            case 1:
            case '1':
            case true:
            case 'TRUE':
            case 'true':
            case 0:
            case '0':
            case false:
            case 'FALSE':
            case 'false':
                break;

            default:
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

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function cleanup($value)
    {
        switch ($value) {
            case 1:
            case '1':
            case true:
            case 'TRUE':
            case 'true':
                return 1;

            default:
                return 0;
        }
    }

    /**
     * Boolean value can never be empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return false;
    }
}
