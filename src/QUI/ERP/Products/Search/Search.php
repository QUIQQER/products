<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Utils\Tables;

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
     * Search language
     *
     * @var string
     */
    protected $lang = null;

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
    abstract public function getEligibleSearchFields();

    /**
     * Gets all unique field values for a specific Field
     *
     * @param QUI\ERP\Products\Field\Field $Field
     * @param bool $activeProductsOnly (optional) - only get values from active products
     * @param integer $catId (optional) - limit values to product category
     * @return array - unique field values
     * @throws QUI\Exception
     */
    protected static function getValuesFromField(
        $Field,
        $activeProductsOnly = true,
        $catId = null
    ) {
        $values = array();
        $column = 'F' . $Field->id;

        $params = array(
            'select' => array($column),
            'from'   => Tables::getProductCacheTableName(),
            'where'  => array()
        );

        if (!is_null($catId)) {
            $params['where']['category_id'] = (int)$catId;
        }

        if ($activeProductsOnly) {
            $params['where']['active'] = 1;
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

        foreach ($result as $row) {
            if (empty($row[$column])) {
                continue;
            }

            // TODO: Relevanten Wert aus Feld holen (sprachabhängig?)

            $values[] = $row[$column];
        }

        $uniqueEntries = array_unique($values);
        $uniqueEntries = array_values($uniqueEntries);

        sort($uniqueEntries);

        return $uniqueEntries;
    }
\QUI\System\Log::writeRecursive();
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
                && $Field->getAttribute('publicField')
                && $Field->getSearchType()
            ) {
                $eligibleFields[] = $Field;
            }
        }

        return $eligibleFields;
    }
}
