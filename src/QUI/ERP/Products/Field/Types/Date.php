<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Year
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

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
    public function validate($value)
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
    public function cleanup($value)
    {
        $Date = new \DateTime($value);

        return $Date->getTimestamp();
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Date';
    }

    /**
     * Return the Backend view
     */
    protected function getBackendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => '',
            'suffix' => '',
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * Return the frontend view
     */
    protected function getFrontendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }
}
