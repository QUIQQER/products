<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\IntType
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class IntType
 * @package QUI\ERP\Products\Field
 */
class IntType extends QUI\ERP\Products\Field\Field
{
    protected $columnType = 'BIGINT';
    protected $searchDataType = Search::SEARCHDATATYPE_NUMERIC;

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
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/IntType';
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
        if (empty($value)) {
            return;
        }

        if (!is_numeric($value)) {
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
     * @return mixed
     */
    public function cleanup($value)
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return [
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTRANGE,
            Search::SEARCHTYPE_INPUTSELECTRANGE,
            Search::SEARCHTYPE_HASVALUE
        ];
    }

    /**
     * Get default search type
     *
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_SELECTRANGE;
    }
}
