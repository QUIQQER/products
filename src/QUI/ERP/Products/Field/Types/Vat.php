<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Vat
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class Vat extends QUI\ERP\Products\Field\Field
{
    protected $searchable = false;

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
        return 'package/quiqqer/products/bin/controls/fields/types/Vat';
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
        if (!is_numeric($value)) {
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

        // exists tax?
        $value = self::cleanup($value);
        $Taxes = new QUI\ERP\Tax\Handler();

        try {
            $Taxes->getTaxType($value);
        } catch (QUI\Exception $Exception) {
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
     * @return integer
     */
    public function cleanup($value)
    {
        return (int)$value;
    }
}
