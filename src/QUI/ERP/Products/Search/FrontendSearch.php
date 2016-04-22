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
    const SITETYPE_SEARCH   = 'quiqqer/products:types/search';
    const SITETYPE_CATEGORY = 'quiqqer/products:types/category';

    /**
     * All site types eligible for frontend search
     *
     * @var array
     */
    protected $eligibleSiteTypes = array(
        self::SITETYPE_CATEGORY => true,
        self::SITETYPE_SEARCH   => true
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
     * @var QUI\Projects\Site
     */
    protected $Site = null;

    /**
     * Site type of frontend search/category site
     *
     * @var string
     */
    protected $siteType = null;

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

        $this->Site     = $Site;
        $this->siteType = $type;
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
        switch ($this->siteType) {
            case self::SITETYPE_CATEGORY:
                $categoryId = $this->Site->getAttribute(
                    'quiqqer.products.settings.categoryId'
                );
                break;

            default:
                $fields = Fields::get
        }
    }
}
