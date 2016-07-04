<?php

/**
 * This file contains QUI\ERP\Products\Search\FrontendSearch
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Search\Cache as SearchCache;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\Utils\Security\Orthos;

/**
 * Class Search
 *
 * @package QUI\ERP\Products\Search
 */
class FrontendSearch extends Search
{
    const SITETYPE_SEARCH = 'quiqqer/products:types/search';
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
     * @throws QUI\Exception
     */
    public function __construct($Site)
    {
        $type = $Site->getAttribute('type');

        if (!isset($this->eligibleSiteTypes[$type])) {
            throw new Exception(array(
                'quiqqer/products',
                'exception.frontendsearch.site.type.not.eligible',
                array(
                    'siteId' => $Site->getId()
                )
            ));
        }

        $this->Site     = $Site;
        $this->lang     = $Site->getProject()->getLang();
        $this->siteType = $type;
    }

    /**
     * Perform machine search and set search parameters via GET/POST
     *
     * @param array $urlParams
     * @param bool $countOnly (optional) - return search result count only [default: false]
     * @return array
     */
    public function searchByUrl($urlParams, $countOnly = false)
    {
        $searchData = array(
            'fields' => array()
        );

        // parse all field specific parameters
        foreach ($urlParams as $k => $v) {
            if (mb_strpos($k, 'F') !== 0) {
                continue;
            }

            preg_match('#\d*#i', $k, $matches);

            if (empty($matches)) {
                continue;
            }

            $v       = $this->sanitizeString($v);
            $fieldId = (int)$matches[0];

            preg_match('#from#i', $k, $from);
            preg_match('#to#i', $k, $to);

            if (!empty($from)
                || !empty($to)
            ) {
                $value = array();

                if (!empty($from)) {
                    $value['from'] = $v;
                }

                if (!empty($to)) {
                    $value['to'] = $v;
                }

                $v = $value;
            }

            $searchData['fields'][$fieldId] = array(
                'value' => $v
            );
        }

        if (isset($urlParams['category']) && !empty($urlParams['category'])) {
            $searchData['category'] = (int)$urlParams['category'];
        }

        if (isset($urlParams['limit']) && !empty($urlParams['limit'])) {
            $searchData['limit'] = $urlParams['limit'];
        }

        if (isset($urlParams['sheet']) && !empty($urlParams['sheet'])) {
            $searchData['sheet'] = (int)$urlParams['sheet'];
        }

        if (isset($urlParams['sortOn']) && !empty($urlParams['sortOn'])) {
            $searchData['sortOn'] = $urlParams['sortOn'];
        }

        if (isset($urlParams['sortBy']) && !empty($urlParams['sortBy'])) {
            $searchData['sortBy'] = $urlParams['sortBy'];
        }

        return $this->search($searchData, $countOnly);
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
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_FRONTEND_EXECUTE
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

        $where[]       = 'lang = :lang';
        $binds['lang'] = array(
            'value' => $this->lang,
            'type'  => \PDO::PARAM_STR
        );

        if (isset($searchParams['category']) &&
            !empty($searchParams['category'])
        ) {
            $where[]           = '`category` LIKE :category';
            $binds['category'] = array(
                'value' => '%,' . (int)$searchParams['category'] . ',%',
                'type'  => \PDO::PARAM_STR
            );
        }

        if (!isset($searchParams['fields'])
            && !isset($searchParams['freetext'])
        ) {
            throw new Exception(
                'Wrong search parameters.',
                400
            );
        }

        // freetext search
        if (isset($searchParams['freetext'])
            && !empty($searchParams['freetext'])
        ) {
            $whereFreeText = array();
            $searchFields  = $this->getSearchFields();
            $value         = $this->sanitizeString($searchParams['freetext']);

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

                $whereFreeText[]              = '`' . $columnName . '` LIKE :freetext' . $fieldId;
                $binds['freetext' . $fieldId] = array(
                    'value' => '%' . $value . '%',
                    'type'  => \PDO::PARAM_STR
                );
            }

            if (!empty($whereFreeText)) {
                $where[] = '(' . implode(' OR ', $whereFreeText) . ')';
            }
        }

        $where[] = '`active` = 1';

        // retrieve query data for fields
        if (isset($searchParams['fields'])) {
            try {
                $queryData = $this->getFieldQueryData($searchParams['fields']);
                $where     = array_merge($where, $queryData['where']);
                $binds     = array_merge($binds, $queryData['binds']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
            }
        }

        // build WHERE query string
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

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
        $cname = 'products/search/frontend/searchfielddata/';
        $cname .= $this->Site->getId() . '/';
        $cname .= $this->lang . '/';
        $cname .= $this->getGroupHashFromUser();

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
            if (!$search) {
                continue;
            }

            $Field = Fields::getField($fieldId);

            if (!$this->canSearchField($Field)) {
                continue;
            }

            $searchFieldDataContent = array(
                'id'          => $Field->getId(),
                'searchType'  => $Field->getSearchType(),
                'title'       => $Field->getTitle($Locale),
                'description' => $Field->getTitle($Locale)
            );

            if (in_array($Field->getSearchType(), $this->searchTypesWithValues)) {
                $searchValues = $this->getValuesFromField($Field, true, $catId);
                $searchParams = array();

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
        $searchFields         = array();
        $searchFieldsFromSite = $this->Site->getAttribute(
            'quiqqer.products.settings.searchFieldIds'
        );

        $eligibleFields = $this->getEligibleSearchFields();

        if (!$searchFieldsFromSite) {
            $searchFieldsFromSite = array();
        } else {
            $searchFieldsFromSite = json_decode($searchFieldsFromSite, true);
        }

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($eligibleFields as $Field) {
            if (!isset($searchFieldsFromSite[$Field->getId()])) {
                $searchFields[$Field->getId()] = false;
                continue;
            }

            $searchFields[$Field->getId()] = boolval(
                $searchFieldsFromSite[$Field->getId()]
            );
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

        foreach ($currentSearchFields as $fieldId => $search) {
            if (isset($searchFields[$fieldId])) {
                $currentSearchFields[$fieldId] = boolval(
                    $searchFields[$fieldId]
                );
            }
        }

        $Edit = $this->Site->getEdit();

        $Edit->setAttribute(
            'quiqqer.products.settings.searchFieldIds',
            json_encode($currentSearchFields)
        );

        $Edit->save();

        return $currentSearchFields;
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

        $this->eligibleFields = $this->filterEligibleSearchFields($fields);

        return $this->eligibleFields;
    }

    /**
     * Gets a unique hash for a user
     *
     * @param QUI\Users\User $User (optional) - If ommitted, User is
     * @return string - md5 hash
     */
    protected function getGroupHashFromUser($User = null)
    {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        $groups = $User->getGroups(false);

        if (is_array($groups)) {
            $groups = implode(',', $groups);
        } else {
            $groups = "";
        }

        return md5($groups);
    }
}
