<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Search\Cache as SearchCache;

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
        $this->lang     = $Site->getAttribute('lang');
        $this->siteType = $type;
    }

    /**
     * Return all fields that are used in the search
     *
     * @return array
     */
    public function getSearchFieldData()
    {
        $cname = 'products/search/fieldvalues/'
                 . $this->Site->getId() . '/' . $this->lang;

        try {
            return SearchCache::get($cname);
        } catch (QUI\Exception $Exception) {
            // nothing, retrieve values
        }

        $searchFieldData = array();
        $parseFields     = $this->getSearchFields();
        $catId           = null;

        switch ($this->siteType) {
            case self::SITETYPE_CATEGORY:
                $catId = $this->Site->getAttribute(
                    'quiqqer.products.settings.categoryId'
                );

                if (!$catId) {
                    $catId = null;
                }
                break;
        }

        $Locale = new QUI\Locale();
        $Locale->setCurrent($this->lang);

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($parseFields as $fieldId => $search) {
            $Field = Fields::getField($fieldId);

            $searchFieldDataContent = array(
                'id'         => $Field->getId(),
                'searchType' => $Field->getSearchType()
            );

            if (in_array($Field->getSearchType(),
                $this->searchTypesWithValues)) {
                $searchValues = $this->getValuesFromField($Field, true, $catId);
                $searchData   = array();

                foreach ($searchValues as $val) {
                    $Field->setValue($val);

                    $searchData[] = array(
                        'label' => $Field->getValueByLocale($Locale),
                        'value' => $val
                    );
                }

                $searchFieldDataContent['searchData'] = $searchData;
            }

            $searchFieldData[] = $searchFieldDataContent;
        }

        SearchCache::set($cname, $searchFieldData);

        return $searchFieldData;
    }

    /**
     * Return all fields that can be used in this search with search status (active/inactive)
     *
     * @return array
     */
    public function getSearchFields()
    {
        $searchFields   = array();
        $searchFieldIds = $this->Site->getAttribute(
            'quiqqer.products.settings.searchFieldIds'
        );

        $eligibleFields = $this->getEligibleSearchFields();

        if (!$searchFieldIds) {
            $searchFieldIds = array();
        } else {
            $searchFieldIds = json_decode($searchFieldIds, true);
        }

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($eligibleFields as $Field) {
            if (!isset($searchFieldIds[$Field->getId()])) {
                $searchFields[$Field->getId()] = false;
                continue;
            }

            $searchFields[$Field->getId()] = boolval(
                $searchFieldIds[$Field->getId()]
            );
        }
    }

    /**
     * Set fields that are searchable
     *
     * @param array $searchFields
     * @return array - search fields
     */
    public function setSearchFields($searchFields)
    {
        $eligibleFields = $this->getEligibleSearchFields();

        foreach ($searchFields as $fieldId => $search) {

        }
    }

    /**
     * Return all fields that are eligible for search
     *
     * Eligible Field = Is of a field type that is generally searchable +
     *                      field is public
     *
     * @return array
     * @throws QUI\Exception
     */
    public function getEligibleSearchFields()
    {
        if (!is_null($this->searchableFields)) {
            return $this->searchableFields;
        }

        switch ($this->siteType) {
            case self::SITETYPE_CATEGORY:
                $categoryId = $this->Site->getAttribute(
                    'quiqqer.products.settings.categoryId'
                );

                if (!$categoryId) {
                    QUI::getMessagesHandler()->addAttention(
                        QUI::getLocale()->get(
                            'quiqqer/products',
                            'attention.frontendsearch.category.site.no.category',
                            array(
                                'siteId' => $this->Site->getId()
                            )
                        )
                    );

                    $fields = Fields::getStandardFields();
                } else {
                    $Category = Categories::getCategory($categoryId);
                    $fields   = $Category->getFields();
                }
                break;

            default:
                $fields = Fields::getStandardFields();
        }

        $this->searchableFields = $this->filterEligibleSearchFields($fields);

        return $this->searchableFields;
    }
}