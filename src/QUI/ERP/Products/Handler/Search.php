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
     * @param array $searchParams (optional) - search parameters for product search
     * @return FrontendSearch
     * @throws QUI\Exception
     */
    public static function getFrontendSearch($Site, $searchParams = null)
    {
        return new FrontendSearch($Site, $searchParams);
    }

    public static function getBackendSearch()
    {
        // TODO
    }
}
