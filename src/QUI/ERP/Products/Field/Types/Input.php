<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Input
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class Input
 * @package QUI\ERP\Products\Field
 */
class Input extends QUI\ERP\Products\Field\Field
{
    protected $columnType = 'TEXT';
    protected $searchDataType = Search::SEARCHDATATYPE_TEXT;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View(array(
            'value'    => $this->cleanup($this->getValue()),
            'title'    => $this->getTitle(),
            'prefix'   => $this->getAttribute('prefix'),
            'suffix'   => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new View(array(
            'value'    => $this->cleanup($this->getValue()),
            'title'    => $this->getTitle(),
            'prefix'   => $this->getAttribute('prefix'),
            'suffix'   => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Input';
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

        if (!is_string($value)
            && !is_numeric($value)
        ) {
            throw new QUI\ERP\Products\Field\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType'  => $this->getType()
                )
            ));
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
        if (!is_string($value)
            && !is_numeric($value)
        ) {
            return null;
        }

        return (string)$value;
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return array(
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE
        );
    }

    /**
     * Get default search type
     *
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_TEXT;
    }
}
