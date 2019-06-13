<?php

/**
 * This file contains QUI\ERP\Products\Search\BackendSearch
 */

namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Search\Cache as SearchCache;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Products;

/**
 * Class Search
 *
 * @package QUI\ERP\Products\Search
 */
class BackendSearch extends Search
{
    /**
     * Flag how the search should handle variant children
     *
     * @var bool
     */
    protected $ignoreVariantChildren = true;

    /**
     * BackendSearch constructor.
     *
     * @param string $lang (optional) - if ommitted, take lang from Product Locale
     */
    public function __construct($lang = null)
    {
        if ($lang === null) {
            $lang = Products::getLocale()->getCurrent();
        }

        $this->lang = $lang;
    }

    /**
     * Execute product search
     *
     * @param array $searchParams - search parameters
     * @param bool $countOnly (optional) - return count of search results only [default: false]
     * @return array|int - product ids
     *
     * @throws QUI\Exception
     */
    public function search($searchParams, $countOnly = false)
    {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_BACKEND_EXECUTE
        );

        $PDO = QUI::getDataBase()->getPDO();

        $binds = [];
        $where = [];

        if ($countOnly) {
            $sql = "SELECT COUNT(*)";
        } else {
            $sql = "SELECT id";
        }

        $sql .= " FROM ".TablesUtils::getProductCacheTableName();

        $where[]       = 'lang = :lang';
        $binds['lang'] = [
            'value' => $this->lang,
            'type'  => \PDO::PARAM_STR
        ];

        if (isset($searchParams['category']) && !empty($searchParams['category'])) {
            $where[]           = '`category` LIKE :category';
            $binds['category'] = [
                'value' => '%,'.(int)$searchParams['category'].',%',
                'type'  => \PDO::PARAM_STR
            ];
        }

        if (isset($searchParams['categories'])
            && !empty($searchParams['categories'])
            && \is_array($searchParams['categories'])
        ) {
            $c               = 0;
            $whereCategories = [];

            foreach ($searchParams['categories'] as $categoryId) {
                $whereCategories[] = '`category` LIKE :category'.$c;

                $binds['category'.$c] = [
                    'value' => '%,'.(int)$categoryId.',%',
                    'type'  => \PDO::PARAM_STR
                ];

                $c++;
            }

            // @todo das OR als setting (AND oder OR) (ist gedacht für die Navigation)
            $where[] = '('.\implode(' OR ', $whereCategories).')';
        }

        if (!isset($searchParams['fields']) && !isset($searchParams['freetext'])) {
            throw new Exception(
                'Wrong search parameters.',
                400
            );
        }

        if (isset($searchParams['active']) && !empty($searchParams['active'])) {
            $where[] = '`active` = 1';
        }

        // freetext search
        if (isset($searchParams['freetext']) && !empty($searchParams['freetext'])) {
            $value = $this->sanitizeString($searchParams['freetext']);

            if (\mb_strpos($value, '#') === 0) {
                $where[]     = '`id` = :id';
                $binds['id'] = [
                    'value' => \preg_replace('#\D#i', '', $value),
                    'type'  => \PDO::PARAM_INT
                ];
            } else {
                $whereFreeText = [];

                // split search value by space
                $freetextValues = \explode(' ', $value);

                foreach ($freetextValues as $value) {
                    // always search tags
                    $whereFreeText[]       = '`tags` LIKE :freetextTags';
                    $binds['freetextTags'] = [
                        'value' => '%,'.$value.',%',
                        'type'  => \PDO::PARAM_STR
                    ];

                    $searchFields = $this->getSearchFields();

                    foreach ($searchFields as $fieldId => $search) {
                        if (!$search) {
                            continue;
                        }

                        $Field = Fields::getField($fieldId);

                        // can only search fields with permission
                        if (!$this->canSearchField($Field)) {
                            continue;
                        }

                        $columnName = SearchHandler::getSearchFieldColumnName($Field);

                        $whereFreeText[]            = '`'.$columnName.'` LIKE :freetext'.$fieldId;
                        $binds['freetext'.$fieldId] = [
                            'value' => '%'.$value.'%',
                            'type'  => \PDO::PARAM_STR
                        ];
                    }
                }

                if (!empty($whereFreeText)) {
                    $where[] = '('.\implode(' OR ', $whereFreeText).')';
                }
            }
        }

        // product types search
        if (isset($searchParams['productTypes'])
            && !empty($searchParams['productTypes'])
            && \is_array($searchParams['productTypes'])
        ) {
            $typeCount = 0;
            $typeWhere = [];

            foreach ($searchParams['productTypes'] as $productType) {
                if (!\class_exists($productType)) {
                    continue;
                }

                $typeWhere[] = 'type = :variantClass'.$typeCount;

                $binds['variantClass'.$typeCount] = [
                    'value' => $productType,
                    'type'  => \PDO::PARAM_STR
                ];
            }

            $where[] = '('.\implode(' OR ', $typeWhere).')';
        } elseif ($this->ignoreVariantChildren) {
            $where[] = 'type <> :variantClass';

            $binds['variantClass'] = [
                'value' => QUI\ERP\Products\Product\Types\VariantChild::class,
                'type'  => \PDO::PARAM_STR
            ];
        }

        // tags search
        if (isset($searchParams['tags'])
            && !empty($searchParams['tags'])
            && \is_array($searchParams['tags'])
        ) {
            $data = $this->getTagQuery($searchParams['tags']);

            if (!empty($data['where'])) {
                $where[] = $data['where'];
                $binds   = \array_merge($binds, $data['binds']);
            }
        }

        // retrieve query data for fields
        if (isset($searchParams['fields'])) {
            try {
                $queryData = $this->getFieldQueryData($searchParams['fields']);
                $where     = \array_merge($where, $queryData['where']);
                $binds     = \array_merge($binds, $queryData['binds']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
            }
        }

        // build WHERE query string
        if (!empty($where)) {
            $sql .= " WHERE ".\implode(" AND ", $where);
        }

        if (!$countOnly) {
            $sql .= " ".$this->validateOrderStatement($searchParams);
        }

        if (isset($searchParams['limit']) &&
            !empty($searchParams['limit']) &&
            !$countOnly
        ) {
            $Pagination = new QUI\Controls\Navigating\Pagination($searchParams);
            $sqlParams  = $Pagination->getSQLParams();
            $sql        .= " LIMIT ".$sqlParams['limit'];
        } else {
            if (!$countOnly) {
                $sql .= " LIMIT ".(int)20; // @todo: standard-limit als setting auslagern
            }
        }

        $Stmt = $PDO->prepare($sql);

        // bind search values
        foreach ($binds as $var => $bind) {
            $Stmt->bindValue(':'.$var, $bind['value'], $bind['type']);
        }

        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            if ($countOnly) {
                return 0;
            }

            return [];
        }

        if ($countOnly) {
            return (int)\current(\current($result));
        }

        $productIds = [];

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
        $cname = 'products/search/backend/fieldvalues/'.$this->lang;

        try {
            return SearchCache::get($cname);
        } catch (QUI\Exception $Exception) {
            // nothing, retrieve values
        }

        $searchFieldData = [];
        $parseFields     = $this->getSearchFields();

        $Locale = new QUI\Locale();
        $Locale->setCurrent($this->lang);

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($parseFields as $fieldId => $search) {
            if (!$search) {
                continue;
            }

            try {
                $Field = Fields::getField($fieldId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
                continue;
            }

            $searchFieldDataContent = [
                'id'         => $Field->getId(),
                'searchType' => $Field->getSearchType()
            ];

            if (\in_array($Field->getSearchType(), $this->searchTypesWithValues)) {
                $searchValues = $this->getValuesFromField($Field, false);
                $searchParams = [];

                foreach ($searchValues as $val) {
                    try {
                        $Field->setValue($val);
                        $label = $Field->getValueByLocale($Locale);
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeException(
                            $Exception,
                            QUI\System\Log::LEVEL_DEBUG
                        );
                        $label = $val;
                    }

                    $searchParams[] = [
                        'label' => $label,
                        'value' => $val
                    ];
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
        $searchFields          = [];
        $PackageCfg            = QUI\ERP\Products\Utils\Package::getConfig();
        $searchFieldIdsFromCfg = $PackageCfg->get('search', 'backend');

        if ($searchFieldIdsFromCfg === false) {
            $searchFieldIdsFromCfg = [];
        } else {
            $searchFieldIdsFromCfg = explode(',', $searchFieldIdsFromCfg);
        }

        $eligibleFields = self::getEligibleSearchFields();

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($eligibleFields as $Field) {
            if (!\in_array($Field->getId(), $searchFieldIdsFromCfg)) {
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
        $newSearchFieldIds   = [];

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
            \implode(',', $newSearchFieldIds)
        );

        try {
            $PackageCfg->save();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // clear search field cache
        SearchCache::clear('products/search/backend/');

        return $this->getSearchFields();
    }

    /**
     * Return all fields that are eligible for search
     *
     * Eligible Field = Is of a field type that is generally searchable +
     *                      field is public
     *
     * @return array
     */
    public function getEligibleSearchFields()
    {
        if (!\is_null($this->eligibleFields)) {
            return $this->eligibleFields;
        }

        $fields               = Fields::getFields();
        $this->eligibleFields = $this->filterEligibleSearchFields($fields);

        return $this->eligibleFields;
    }

    /**
     * The search considers variant children
     */
    public function considerVariantChildren()
    {
        $this->ignoreVariantChildren = false;
    }

    /**
     * The search ignores variant children
     * Children are therefore not displayed in the search.
     */
    public function ignoreVariantChildren()
    {
        $this->ignoreVariantChildren = true;
    }
}
