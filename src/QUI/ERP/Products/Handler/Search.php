<?php

/**
 * This file contains QUI\ERP\Products\Handler\Search
 */
namespace QUI\ERP\Products\Handler;

use QUI;
use Stash;
use QUI\ERP\Products\Utils\Package as PackageUtils;
use QUI\ERP\Products\Search\FrontendSearch;

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
     * Data types for search values
     */
    const SEARCHDATATYPE_TEXT    = 1;
    const SEARCHDATATYPE_NUMERIC = 2;
    const SEARCHDATATYPE_JSON    = 3;

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

    /**
     * Get FrontendSearch
     *
     * @param QUI\Projects\Site $Site - Search Site or Category Site
     * @return FrontendSearch
     * @throws QUI\Exception
     */
    public static function getFrontendSearch($Site)
    {
        return new FrontendSearch($Site);
    }

    public static function getBackendSearch()
    {
        // TODO
    }

    /**
     * Get column name for search fields
     *
     * @param QUI\ERP\Products\Field\Field $Field
     * @return string
     */
    public static function getSearchFieldColumnName($Field)
    {
        return 'F' .  $Field->getId();
    }
}
