<?php

/**
 * This file contains QUI\ERP\Products\Search\BackendSearch
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Search\Cache as SearchCache;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\Utils\Security\Orthos;
use QUI\ERP\Products\Handler\Products;

/**
 * Class Search
 *
 * @package QUI\ERP\Products\Search
 */
class BackendSearch extends Search
{
    /**
     * BackendSearch constructor.
     *
     * @param string $lang (optional) - if ommitted, take lang from Product Locale
     * @throws QUI\Exception
     */
    public function __construct($lang = null)
    {
        if (is_null($lang)) {
            $lang = Products::getLocale()->getCurrent();
        }

        $this->lang = $lang;
    }

    /**
     * Execute product search
     *
     * @param array $searchParams - search parameters
     * @param bool $countOnly (optional) - return count of search results only [default: false]
     * @return array - product ids
     * @throws QUI\Exception
     */
    public function search($searchParams, $countOnly = false)
    {
        QUI\Rights\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_EXECUTE
        );

        $PDO = QUI::getDataBase()->getPDO();

        $binds = array();
        $where = array();

        if ($countOnly) {
            $sql = "SELECT COUNT(*)";
        } else {
            $sql = "SELECT id";
        }

        $sql .= " FROM " . TablesUtils::getProductCacheTableName();

        if (isset($searchParams['category']) &&
            !empty($searchParams['category'])
        ) {
            $where[] = array(
                '`category` = :category'
            );

            $binds[] = array(
                'category' => array(
                    'value' => (int)$searchParams['category'],
                    'type' => \PDO::PARAM_INT
                )
            );
        }

        if (!isset($searchParams['fields'])) {
            throw new QUI\Exception(
                'Wrong search parameters.',
                400
            );
        }

        if (isset($searchParams['active'])
            && !empty($searchParams['active'])
        ) {
            $where[] = '`active` = 1';
        }

        // retrieve query data for fields
        try {
            $queryData = $this->getFieldQueryData($searchParams['fields']);
            $where     = array_merge($where, $queryData['where']);
            $binds     = array_merge($binds, $queryData['binds']);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        // build WHERE query string
        $sql .= " WHERE " . implode(" AND ", $where);

        if (isset($searchParams['sortOn']) &&
            !empty($searchParams['sortOn'])
        ) {
            $order = "ORDER BY " . Orthos::clear($searchParams['sortOn']);

            if (isset($searchParams['sortBy']) &&
                !empty($searchParams['sortBy'])
            ) {
                $order .= " " . Orthos::clear($searchParams['sortBy']);
            } else {
                $order .= " ASC";
            }

            $sql .= " " . $order;
        }

        if (isset($searchParams['limit']) &&
            !empty($searchParams['limit'])
        ) {
            $Pagination = new QUI\Bricks\Controls\Pagination($searchParams);
            $sqlParams  = $Pagination->getSQLParams();
            $sql .= " LIMIT " . $sqlParams['limit'];
        } else {
            if (!$countOnly) {
                $sql .= " LIMIT " . (int)20; // @todo: standard-limit als setting auslagern
            }
        }
QUI\System\Log::writeRecursive($sql);
        $Stmt = $PDO->prepare($sql);

        // bind search values
        foreach ($binds as $var => $bind) {
            $Stmt->bindValue(':' . $var, $bind['value'], $bind['type']);
        }

        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            if ($countOnly) {
                return 0;
            }

            return array();
        }

        if ($countOnly) {
            return (int)current(current($result));
        }

        $productIds = array();

        foreach ($result as $row) {
            $productIds[] = $row['id'];
        }

        return $productIds;
    }

    /**
     * Return all fields that are used in the search
     *
     * @return array
     */
    public function getSearchFieldData()
    {
        $cname = 'products/search/backend/fieldvalues/' . $this->lang;

        try {
            return SearchCache::get($cname);
        } catch (QUI\Exception $Exception) {
            // nothing, retrieve values
        }

        $searchFieldData = array();
        $parseFields     = $this->getSearchFields();

        $Locale = new QUI\Locale();
        $Locale->setCurrent($this->lang);

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($parseFields as $fieldId => $search) {
            if (!$search) {
                continue;
            }

            $Field = Fields::getField($fieldId);

            $searchFieldDataContent = array(
                'id' => $Field->getId(),
                'searchType' => $Field->getSearchType()
            );

            if (in_array($Field->getSearchType(), $this->searchTypesWithValues)) {
                $searchValues = $this->getValuesFromField($Field, false);
                $searchParams = array();

                foreach ($searchValues as $val) {
                    try {
                        $Field->setValue($val);
                        $label = $Field->getValueByLocale($Locale);
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_DEBUG);
                        $label = $val;
                    }

                    $searchParams[] = array(
                        'label' => $label,
                        'value' => $val
                    );
                }

                $searchFieldDataContent['searchData'] = $searchParams;
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
        $searchFields          = array();
        $PackageCfg            = QUI\ERP\Products\Utils\Package::getConfig();
        $searchFieldIdsFromCfg = $PackageCfg->get('search', 'backend');

        if ($searchFieldIdsFromCfg === false) {
            $searchFieldIdsFromCfg = array();
        } else {
            $searchFieldIdsFromCfg = explode(',', $searchFieldIdsFromCfg);
        }

        $eligibleFields = self::getEligibleSearchFields();

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($eligibleFields as $Field) {
            if (!in_array($Field->getId(), $searchFieldIdsFromCfg)) {
                $searchFields[$Field->getId()] = false;
                continue;
            }

            $searchFields[$Field->getId()] = true;
        }

        return $searchFields;
    }

    /**
     * Set fields that are searchable
     *
     * @param array $searchFields
     * @return array - search fields
     */
    public function setSearchFields($searchFields)
    {
        $currentSearchFields = $this->getSearchFields();
        $newSearchFieldIds   = array();

        foreach ($currentSearchFields as $fieldId => $search) {
            if (isset($searchFields[$fieldId])
                && $searchFields[$fieldId]
            ) {
                $newSearchFieldIds[] = $fieldId;
            } else {
                unset($currentSearchFields[$fieldId]);
            }
        }

        $PackageCfg = QUI\ERP\Products\Utils\Package::getConfig();

        $PackageCfg->set(
            'search',
            'backend',
            implode(',', $newSearchFieldIds)
        );

        $PackageCfg->save();

        return $this->getSearchFields();
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
        if (!is_null($this->eligibleFields)) {
            return $this->eligibleFields;
        }

        $fields               = Fields::getFields();
        $this->eligibleFields = $this->filterEligibleSearchFields($fields);

        return $this->eligibleFields;
    }
}
