<?php

/**
 * This file contains QUI\ERP\Products\Handler\Search
 */

namespace QUI\ERP\Products\Handler;

use QUI;
use QUI\ERP\Products\Search\FrontendSearch;
use QUI\ERP\Products\Search\BackendSearch;

/**
 * Class Fields
 * @package QUI\ERP\Products\Handler
 */
class Search
{
    /**
     * Search types
     */
    const SEARCHTYPE_TEXT = 'text';
    const SEARCHTYPE_SELECTRANGE = 'selectRange';
    const SEARCHTYPE_INPUTSELECTRANGE = 'inputSelectRange';
    const SEARCHTYPE_SELECTSINGLE = 'selectSingle';
    const SEARCHTYPE_INPUTSELECTSINGLE = 'inputSelectSingle';
    const SEARCHTYPE_SELECTMULTI = 'selectMulti';
    const SEARCHTYPE_BOOL = 'bool';
    const SEARCHTYPE_HASVALUE = 'hasValue';
    const SEARCHTYPE_DATE = 'date';
    const SEARCHTYPE_DATERANGE = 'dateRange';

    /**
     * Data types for search values
     */
    const SEARCHDATATYPE_TEXT = 1;
    const SEARCHDATATYPE_NUMERIC = 2;
    const SEARCHDATATYPE_JSON = 3;

    /**
     * Search permissions
     */
    const PERMISSION_FRONTEND_EXECUTE = 'search.frontend.execute';
    const PERMISSION_FRONTEND_CONFIGURE = 'search.frontend.configure';
    const PERMISSION_BACKEND_EXECUTE = 'search.backend.execute';
    const PERMISSION_BACKEND_CONFIGURE = 'search.backend.configure';

    /**
     * Get all available search types
     *
     * @return array
     */
    public static function getSearchTypes()
    {
        return [
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
        ];
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

    /**
     * Get BackEndSearch
     *
     * @param string $lang (optional) - if ommitted, take lang from Product Locale
     * @return BackendSearch
     * @throws QUI\Exception
     */
    public static function getBackendSearch($lang = null)
    {
        return new BackendSearch($lang);
    }

    /**
     * Get column name for search fields
     *
     * @param QUI\ERP\Products\Interfaces\FieldInterface $Field
     * @return string
     */
    public static function getSearchFieldColumnName(QUI\ERP\Products\Interfaces\FieldInterface $Field)
    {
        return 'F'.$Field->getId();
    }
}
