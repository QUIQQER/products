<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Year
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class Price
 * @package QUI\ERP\Products\Field
 */
class Date extends QUI\ERP\Products\Field\Field
{
    /**
     * Validates a year value
     *
     * @param mixed $value
     * @throws QUI\Exception
     */
    public static function validate($value)
    {
        $dateTime = \DateTime::createFromFormat('m/d/Y', $value);

        if ($dateTime === false) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.value.not.allowed')
            );
        }

        $errors = \DateTime::getLastErrors();

        if (!empty($errors['warning_count'])) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.value.not.allowed')
            );
        }
    }

    /**
     * @param mixed $value
     * @return int - timestamp
     */
    public static function cleanup($value)
    {
        $Date = new \DateTime($value);

        return $Date->getTimestamp();
    }

    /**
     * Return the Backend view
     */
    protected function getBackendView()
    {
        // TODO: Implement getBackendView() method.

        return new View(array(
            'value' => '',
            'title' => '',
            'prefix' => '',
            'suffix' => '',
            'priority' => ''
        ));
    }

    /**
     * Return the frontend view
     */
    protected function getFrontendView()
    {
        // TODO: Implement getFrontendView() method.

        return new View(array(
            'value' => '',
            'title' => '',
            'prefix' => '',
            'suffix' => '',
            'priority' => ''
        ));
    }
}
