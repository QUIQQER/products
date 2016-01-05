<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Year
 */
namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class Price
 * @package QUI\ERP\Products\Field
 */
class Date extends QUI\ERP\Products\Field\Modell
{
    /**
     * Validates a year value
     *
     * @param mixed $value
     * @throws QUI\Exception
     */
    public function checkValue($value)
    {
        $value = (string)$value;

        if (trim($value) === '') {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.value.not.allowed')
            );
        }

        $value = preg_replace('#[^\d]#i', '', $value);

        return (int)$value;
    }
}
