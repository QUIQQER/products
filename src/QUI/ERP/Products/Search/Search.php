<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Utils\Tables;
use QUI\ERP\Products\Handler\Search as SearchHandler;

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
     * All fields that are eligible for search
     *
     * @var array
     */
    protected $eligibleFields = null;

    /**
     * Search language
     *
     * @var string
     */
    protected $lang = null;

    /**
     * All search types that need cached values
     *
     * @var array
     */
    protected $searchTypesWithValues = array(
        SearchHandler::SEARCHTYPE_INPUTSELECTRANGE,
        SearchHandler::SEARCHTYPE_INPUTSELECTSINGLE,
        SearchHandler::SEARCHTYPE_SELECTMULTI,
        SearchHandler::SEARCHTYPE_SELECTRANGE,
        SearchHandler::SEARCHTYPE_SELECTSINGLE
    );

    /**
     * Return all fields with values and labels (used for building search control)
     *
     * @return array
     */
    abstract public function getSearchFieldData();

    /**
     * Return all fields that can be used in this search with search status (active/inactive)
     *
     * @return array
     */
    abstract public function getSearchFields();

    /**
     * Set fields that are searchable
     *
     * @param array $searchFields
     * @return array - search fields
     */
    abstract public function setSearchFields($searchFields);

    /**
     * Return all fields that are searchable
     *
     * Searchable Field = Is of a field type that is generally searchable +
     *                      field is public
     *
     * @return array
     */
    abstract public function getEligibleSearchFields();

    /**
     * Execute product search
     *
     * @param array $searchParams - search parameters
     * @param bool $countOnly (optional) - return count of search results only [default: false]
     * @return array - product ids
     * @throws QUI\Exception
     */
    abstract public function search($searchParams, $countOnly = false);

    /**
     * Gets all unique field values for a specific Field
     *
     * @param QUI\ERP\Products\Field\Field $Field
     * @param bool $activeProductsOnly (optional) - only get values from active products
     * @param integer $catId (optional) - limit values to product category
     * @return array - unique field values
     * @throws QUI\Exception
     */
    protected function getValuesFromField(
        $Field,
        $activeProductsOnly = true,
        $catId = null
    ) {
        $values = array();
        $column = SearchHandler::getSearchFieldColumnName($Field);

        $params = array(
            'select' => array(),
            'from'   => Tables::getProductCacheTableName(),
            'where'  => array(
                'lang' => $this->lang
            )
        );

        if (!is_null($catId)) {
            $params['where']['category'] = (int)$catId;
        }

        if ($activeProductsOnly) {
            $params['where']['active'] = 1;
        }

        // special queries depending on search type
        switch ($Field->getSearchDataType()) {
            case SearchHandler::SEARCHDATATYPE_NUMERIC:

                switch ($Field->getSearchType()) {
                    case SearchHandler::SEARCHTYPE_SELECTSINGLE:
                    case SearchHandler::SEARCHTYPE_INPUTSELECTSINGLE:
                    case SearchHandler::SEARCHTYPE_SELECTRANGE:
                    case SearchHandler::SEARCHTYPE_INPUTSELECTRANGE:

                        $params['select'] = array(
                            'MIN(' . $column . ')',
                            'MAX(' . $column . ')'
                        );

                        break;
                }

                break;

            case SearchHandler::SEARCHDATATYPE_TEXT:

                switch ($Field->getSearchType()) {
                    case SearchHandler::SEARCHTYPE_SELECTSINGLE:
                    case SearchHandler::SEARCHTYPE_INPUTSELECTSINGLE:

                        $params['select'] = array($column);
                        $params['group']  = $column;

                        break;

                    case SearchHandler::SEARCHTYPE_SELECTMULTI:

                        $params['select'] = array($column);

                        break;
                }

                break;
        }

        try {
            $result = QUI::getDataBase()->fetch($params);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError(
                'Search::getValuesFromField -> Could not retrieve values of'
                . ' field #' . $Field->getId() . ' -> ' . $Exception->getMessage()

            );

            return array();
        }

        if (empty($result)) {
            return $values;
        }

        switch ($Field->getSearchDataType()) {
            case SearchHandler::SEARCHDATATYPE_NUMERIC:
                $values = $Field->calculateValueRange(
                    $result[0]['MAX(' . $column . ')'],
                    $result[0]['MIN(' . $column . ')']
                );
                break;

            case SearchHandler::SEARCHDATATYPE_TEXT:
                foreach ($result as $row) {
                    if (empty($row[$column])) {
                        continue;
                    }

                    $values[] = $row[$column];
                }
                break;
        }

//        $uniqueEntries = array_unique($values);
//        $uniqueEntries = array_values($uniqueEntries);

        sort($values);

        return $values;
    }

    /**
     * Filters all fields that are not eligible for use in search
     *
     * @param array $fields - array with Field objects
     * @return array
     */
    protected function filterEligibleSearchFields($fields)
    {
        $eligibleFields = array();

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            if ($Field->isSearchable()
                && $Field->isPublic()
                && $Field->getSearchType()
            ) {
                $eligibleFields[] = $Field;
            }
        }

        return $eligibleFields;
    }

    /**
     * Checks if the currently logged in user is allowed to search a category field
     *
     * @param QUI\ERP\Products\Field\Field $Field
     * @param QUI\Users\User $User (optional)
     * @return bool
     */
    protected function canSearchField($Field, $User = null)
    {
        $viewPermission = $Field->hasViewPermission($User);

        if (!$viewPermission) {
            return false;
        }

        $eligibleFields = $this->getEligibleSearchFields();

        foreach ($eligibleFields as $EligibleField) {
            if ($Field->getId() === $EligibleField->getId()) {
                return true;
            }
        }

        return false;
    }
}
