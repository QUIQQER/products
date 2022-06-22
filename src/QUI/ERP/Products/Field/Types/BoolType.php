<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\BoolType
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

use function is_bool;
use function is_int;
use function is_numeric;

/**
 * Class Price
 * @package QUI\ERP\Products\Field
 */
class BoolType extends QUI\ERP\Products\Field\Field
{
    protected $columnType = 'TINYINT(1)';

    protected $defaultValue = 0;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new BoolTypeFrontendView($this->getFieldDataForView());
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
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if ($value === '') {
            return;
        }

        if (is_bool($value)) {
            return;
        }

        if (is_int($value)) {
            return;
        }

        if (is_numeric($value)) {
            return;
        }

        switch ($value) {
            case 'TRUE':
            case 'true':
            case 'FALSE':
            case 'false':
                return;
        }

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

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return int - 1 or 0
     */
    public function cleanup($value)
    {
        if ($value === '') {
            return 0;
        }

        if ($value === true) {
            return 1;
        }

        if (is_numeric($value)) {
            return (int)$value ? 1 : 0;
        }

        switch ($value) {
            case 'TRUE':
            case 'true':
                return 1;
        }

        return 0;
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

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return [
            Search::SEARCHTYPE_BOOL,
            Search::SEARCHTYPE_CHECKBOX
        ];
    }

    /**
     * Get default search type
     *
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_BOOL;
    }
}
