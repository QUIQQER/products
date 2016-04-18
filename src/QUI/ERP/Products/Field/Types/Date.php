<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Year
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Products as ProductsHandler;

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
        $dateTime = true;

        try {
            new \DateTime($value);
        } catch (\Exception $Exception) {
            $dateTime = false;
        }

        if ($dateTime === false) {
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

        $errors = \DateTime::getLastErrors();

        if (!empty($errors['warning_count'])) {
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
     * @param mixed $value
     * @return int - timestamp
     */
    public function cleanup($value)
    {
        try {
            $Date = new \DateTime($value);
        } catch (\Exception $Exception) {
            $Date = new \DateTime();
        }

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
            'value' => ProductsHandler::getLocale()->formatDate($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * Return the frontend view
     */
    protected function getFrontendView()
    {
        return new View(array(
            'value' => ProductsHandler::getLocale()->formatDate($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }
}
