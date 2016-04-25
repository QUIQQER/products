<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Categories;

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
        $this->siteType = $type;
    }

    /**
     * Return all fields that are used in the search
     *
     * @return array
     */
    public function getSearchFields()
    {
        /**
         * 1. Feld-IDs aus Such-/Kategorie-Seite holen
         * 2. Ggf. mit eligible fields abgleichen
         * 3. search values für jedes feld holen (aus cache oder neu)
         * 4. array mit such-informationen zurückgeben
         */

        $cname = 'quiqqer/products/search/fieldvalues/' . $Field->getId();

        try {
            return QUI\Cache\Manager::get($cname);
        } catch (QUI\Exception $Exception) {
            // nothing, retrieve values
        }

        $searchFields   = array();
        $searchFieldIds = $this->Site->getAttribute(
            'quiqqer.products.settings.searchFieldIds'
        );

        if (!$searchFieldIds) {
            return $searchFields;
        }

        $searchFieldIds = json_decode($searchFieldIds, true);

        foreach ($searchFieldIds as $fieldId) {
            $Field          = Fields::getField($fieldId);
            $searchFields[] = $Field;
        }

        $searchFields = $this->filterEligibleSearchFields($searchFields);
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
