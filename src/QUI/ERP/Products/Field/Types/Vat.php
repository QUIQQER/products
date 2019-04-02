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
    /**
     * @var string
     */
    protected $columnType = 'SMALLINT';

    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Vat';
    }

    /**
     * Return the frontend view
     */
    public function getFrontendView()
    {
        return new VatFrontendView($this->getFieldDataForView());
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

        if ($value === -1) {
            return;
        }

        if (\strpos($value, ':') !== false) {
            $value = \explode(':', $value);

            if (isset($value[1])) {
                $value = (int)$value[1];
            } else {
                $value = false;
            }
        }

        if (!\is_numeric($value)) {
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

        // exists tax?
        $value = self::cleanup($value);
        $Taxes = new QUI\ERP\Tax\Handler();

        try {
            $Taxes->getTaxType($value);
        } catch (QUI\Exception $Exception) {
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

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return integer
     */
    public function cleanup($value)
    {
        if ($value === '') {
            return -1;
        }

        if (\strpos($value, ':') !== false) {
            $value = \explode(':', $value);

            if (isset($value[1])) {
                $value = $value[1];
            } else {
                $value = -1;
            }
        }

        return (int)$value;
    }
}
