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
class FrontendSearch extends Search
{
    /**
     * All site types eligible for frontend search
     *
     * @var array
     */
    protected $eligibleSiteTypes = array(
        'quiqqer/products:types/category' => true
    );

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
     * The frontend Site where the search is conducted
     *
     * @var null
     */
    protected $Site = null;

    /**
     * FrontendSearch constructor.
     *
     * @param QUI\Projects\Site $Site - Search Site or Category Site
     * @param array $searchParams (optional) - search parameters for product search
     * @throws QUI\Exception
     */
    public function __construct($Site, $searchParams = null)
    {
        $type = $Site->getAttribute('type');

        if (!isset($this->eligibleSiteTypes[$type])) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.frontendsearch.site.type.not.eligible',
                array(
                    'siteId' => $Site->getId()
                )
            ));
        }

        $this->Site = $Site;
    }

    /**
     * Return all fields that are used in the search
     *
     * @return array
     */
    public function getSearchFields()
    {

    }

    /**
     * Return all fields that are searchable
     *
     * Searchable Field = Is of a field type that is generally searchable +
     *                      field is public
     *
     * @return array
     */
    public function getSearchableFields()
    {
        
    }
}
