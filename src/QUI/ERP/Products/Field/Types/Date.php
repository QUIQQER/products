<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Year
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class Price
 * @package QUI\ERP\Products\Field
 */
class Date extends QUI\ERP\Products\Field\Field
{
    protected $columnType = 'INT(11)';

    /**
     * Validates a year value
     *
     * @param mixed $value
     * @throws QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        $dateTime = true;

        if (\is_numeric($value)) {
            $value = \strtotime($value);
        }

        try {
            new \DateTime($value);
        } catch (\Exception $Exception) {
            $dateTime = false;
        }

        if ($dateTime === false) {
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

        $errors = \DateTime::getLastErrors();

        if (!empty($errors['warning_count'])) {
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
     * @param mixed $value
     * @return int - timestamp
     */
    public function cleanup($value)
    {
        if (\is_numeric($value)) {
            $value = \strtotime($value);
        }

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
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * Return the frontend view
     */
    public function getFrontendView()
    {
        return new DateFrontendView($this->getFieldDataForView());
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return [
            Search::SEARCHTYPE_DATE,
            Search::SEARCHTYPE_DATERANGE
        ];
    }

    /**
     * Get default search type
     *
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_DATERANGE;
    }
}
