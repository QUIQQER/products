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
    const SEARCHTYPE_TEXT              = 1;
    const SEARCHTYPE_SELECTRANGE       = 2;
    const SEARCHTYPE_INPUTSELECTRANGE  = 3;
    const SEARCHTYPE_SELECTSINGLE      = 4;
    const SEARCHTYPE_INPUTSELECTSINGLE = 5;
    const SEARCHTYPE_SELECTMULTI       = 6;
    const SEARCHTYPE_BOOL              = 7;
    const SEARCHTYPE_HASVALUE          = 8;
    const SEARCHTYPE_DATE              = 9;
    const SEARCHTYPE_DATERANGE         = 10;

    /**
     * Get all available search types
     *
     * @return array
     */
    public static function getAllSearchTypes()
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

}
