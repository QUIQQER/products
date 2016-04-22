<?php

/**
 * This file contains QUI\ERP\Products\Handler\Search
 */
namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Fields
 * @package QUI\ERP\Products\Handler
 */
class Search
{
    /**
     * Search types
     */
    const SEARCHTYPE_TEXT              = 'text';
    const SEARCHTYPE_SELECTRANGE       = 'selectRange';
    const SEARCHTYPE_INPUTSELECTRANGE  = 'inputSelectRange';
    const SEARCHTYPE_SELECTSINGLE      = 'selectSingle';
    const SEARCHTYPE_INPUTSELECTSINGLE = 'inputSelectSingle';
    const SEARCHTYPE_SELECTMULTI       = 'selectMulti';
    const SEARCHTYPE_BOOL              = 'bool';
    const SEARCHTYPE_HASVALUE          = 'hasValue';
    const SEARCHTYPE_DATE              = 'date';
    const SEARCHTYPE_DATERANGE         = 'dateRange';

    /**
     * Get all available search types
     *
     * @return array
     */
    public static function getSearchTypes()
    {
        return array(
            self::SEARCHTYPE_TEXT,
            self::SEARCHTYPE_SELECTRANGE,
            self::SEARCHTYPE_SELECTSINGLE,
            self::SEARCHTYPE_SELECTMULTI,
            self::SEARCHTYPE_BOOL,
            self::SEARCHTYPE_HASVALUE,
            self::SEARCHTYPE_DATE,
            self::SEARCHTYPE_DATERANGE,
            self::SEARCHTYPE_INPUTSELECTRANGE,
            self::SEARCHTYPE_INPUTSELECTSINGLE
        );
    }

    public static function getFrontendSearch()
    {

    }

    public static function getBackendSearch()
    {
        
    }

    /**
     * Get search data for searchable fields (used in search control)
     *
     * @param string $lang - language for field names and values
     * @param integer $categoryId (optional) - search data for specific category
     */
    public static function getSearchFieldsData($lang, $categoryId = null)
    {
        
    }

    protected static function getSearchValues($lang, $categoryId = null)
    {

    }
}
