<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class Search
 *
 * @package QUI\ERP\Products\Search
 */
abstract class Search extends QUI\QDOM
{
    /**
     * All fields that are used in the search
     *
     * @var array
     */
    protected $searchFields = null;

    /**
     * All fields that are searchable
     *
     * @var array
     */
    protected $searchableFields = null;

    /**
     * Return all fields that are used in the search
     * 
     * @return array
     */
    abstract public function getSearchFields();

    /**
     * Return all fields that are searchable
     *
     * Searchable Field = Is of a field type that is generally searchable +
     *                      field is public
     *
     * @return array
     */
    abstract public function getSearchableFields();
}
