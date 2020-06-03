<?php

/**
 * This file contains QUI\ERP\Products\Search\FrontendSearch
 */

namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Search\Cache as SearchCache;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Products;

/**
 * Class Search
 *
 * @package QUI\ERP\Products\Search
 */
class FrontendSearch extends Search
{
    const SITETYPE_SEARCH   = 'quiqqer/products:types/search';
    const SITETYPE_CATEGORY = 'quiqqer/products:types/category';
    const SITETYPE_LIST     = 'quiqqer/productstags:types/list';

    /**
     * Flag how the search should handle variant children
     *
     * @var bool
     */
    protected $ignoreVariantChildren = true;

    /**
     * All site types eligible for frontend search
     *
     * @var array
     */
    protected $eligibleSiteTypes = [
        self::SITETYPE_CATEGORY => true,
        self::SITETYPE_SEARCH   => true,
        self::SITETYPE_LIST     => true
    ];

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
     * ID of product category assigned to site
     *
     * @var int
     */
    protected $categoryId = null;

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
            throw new Exception([
                'quiqqer/products',
                'exception.frontendsearch.site.type.not.eligible',
                [
                    'siteId' => $Site->getId()
                ]
            ]);
        }

        $this->categoryId = $Site->getAttribute('quiqqer.products.settings.categoryId');

        $this->Site     = $Site;
        $this->lang     = $Site->getProject()->getLang();
        $this->siteType = $type;

        // global variant settings
        if (QUI::getPackage('quiqqer/products')->getConfig()->get('variants', 'findChildrenInSearch')) {
            $this->ignoreVariantChildren = false;
        }
    }

    /**
     * Execute product search
     *
     * @param array $searchParams - search parameters
     * @param bool $countOnly (optional) - return count of search results only [default: false]
     * @return array|int - product ids
     * @throws QUI\Exception
     */
    public function search($searchParams, $countOnly = false)
    {
        QUI\Permissions\Permission::checkPermission(
            SearchHandler::PERMISSION_FRONTEND_EXECUTE
        );

        $SearchQueryCollector = new SearchQueryCollector($this, $searchParams);
        QUI::getEvents()->fireEvent('quiqqerProductsFrontendSearchExecute', [$SearchQueryCollector]);

        // Get search params that may have been modified during quiqqerProductsFrontendSearchStart event
        $searchParams = $SearchQueryCollector->getSearchParams();

        $searchTerm = false;
        $PDO        = QUI::getDataBase()->getPDO();
        $binds      = [];
        $where      = [];

        $findVariantParentsByChildValues = empty($searchParams['ignoreFindVariantParentsByChildValues'])
                                           && !!(int)QUI::getPackage('quiqqer/products')
                ->getConfig()
                ->get('variants', 'findVariantParentByChildValues');

        $sql = "SELECT `id`, `type`, `parentId`, `productNo`";
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
        } elseif ($this->categoryId) {
            $where[]           = '`category` LIKE :category';
            $binds['category'] = [
                'value' => '%,'.$this->categoryId.',%',
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
            if (!empty($whereCategories)) {
                $where[] = '('.\implode(' OR ', $whereCategories).')';
            }
        }

        if (!isset($searchParams['fields']) && !isset($searchParams['freetext'])) {
            throw new Exception(
                'Wrong search parameters.',
                400
            );
        }

        // freetext search
        if (isset($searchParams['freetext']) && !empty($searchParams['freetext'])) {
            $whereFreeText = [];
            $value         = $this->sanitizeString($searchParams['freetext']);
            $searchTerm    = $value;

            // split search value by space
            $freetextValues = \explode(' ', $value);

            foreach ($freetextValues as $value) {
                // always search tags
                $whereFreeText[]       = '`tags` LIKE :freetextTags';
                $binds['freetextTags'] = [
                    'value' => '%,'.$value.',%',
                    'type'  => \PDO::PARAM_STR
                ];

                //$searchFields = $this->getSearchFields();
                $searchFields = QUI\ERP\Products\Utils\Search::getDefaultFrontendFreeTextFields();

                foreach ($searchFields as $Field) {
                    /* @var $Field QUI\ERP\Products\Field\Field */
                    // can only search fields with permission
                    if (!$this->canSearchField($Field)) {
                        continue;
                    }

                    $columnName = SearchHandler::getSearchFieldColumnName($Field);
                    $fieldId    = $Field->getId();

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

        // tags search
        $siteTags = $this->Site->getAttribute('quiqqer.products.settings.tags');

        if (!empty($siteTags)) {
            $siteTags = \explode(',', \trim($siteTags, ','));
        }

        if (!\is_array($siteTags)) {
            $siteTags = [];
        }

        if (isset($searchParams['tags'])
            && !empty($searchParams['tags'])
            && \is_array($searchParams['tags'])
        ) {
            $siteTags = \array_merge($siteTags, $searchParams['tags']);
        }

        if (!empty($siteTags)) {
            $data = $this->getTagQuery($siteTags);

            if (!empty($data['where'])) {
                $where[] = $data['where'];
                $binds   = \array_merge($binds, $data['binds']);
            }
        }

        // product permissions
        if (Products::usePermissions()) {
            // user
            $User = QUI::getUserBySession();

            $whereOr = [
                '`viewUsersGroups` IS NULL'
            ];

            if ($User->getId()) {
                $whereOr[] = '`viewUsersGroups` LIKE :permissionUser';

                $binds['permissionUser'] = [
                    'value' => '%,u'.$User->getId().',%',
                    'type'  => \PDO::PARAM_STR
                ];
            }

            // user groups
            $userGroupIds = $User->getGroups(false);
            $i            = 0;

            foreach ($userGroupIds as $groupId) {
                $whereOr[] = '`viewUsersGroups` LIKE :permissionGroup'.$i;

                $binds['permissionGroup'.$i] = [
                    'value' => '%,g'.$groupId.',%',
                    'type'  => \PDO::PARAM_STR
                ];

                $i++;
            }

            $where[] = '('.\implode(' OR ', $whereOr).')';
        }

        $where[] = '`active` = 1';

        // retrieve query data for fields
        if (isset($searchParams['fields'])) {
            try {
                $queryData = $this->getFieldQueryData($searchParams['fields']);
                $where     = \array_merge($where, $queryData['where']);
                $binds     = \array_merge($binds, $queryData['binds']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
            }
        }

        if (!$findVariantParentsByChildValues && $this->ignoreVariantChildren) {
            $where[] = 'type <> :variantClass';

            $binds['variantClass'] = [
                'value' => VariantChild::class,
                'type'  => \PDO::PARAM_STR
            ];
        }

        // Add WHERE statements via event
        $where = array_merge($SearchQueryCollector->getWhereStatements(), $where);
        $binds = array_merge($SearchQueryCollector->getBinds(), $binds);

        // build WHERE query string
        if (!empty($where)) {
            $sql .= " WHERE ".\implode(" AND ", $where);
        }

        if (!$countOnly) {
            $sql .= " ".$this->validateOrderStatement($searchParams);
        }

        $limitOffset = false;

        if (!empty($searchParams['limit']) && !$countOnly) {
            $limit = explode(',', $searchParams['limit']);

            if (!empty($limit[1])) {
                $searchParams['sheet'] = (int)$limit[0] ?: 1;
                $searchParams['limit'] = (int)$limit[1];
            } else {
                if (empty($searchParams['sheet'])) {
                    $searchParams['sheet'] = 1;
                }

                if (empty($searchParams['limit'])) {
                    $searchParams['limit'] = (int)$limit[0];
                }
            }

            if (!empty($searchParams['limitOffset'])) {
                $sql         .= " LIMIT ".(int)$searchParams['limitOffset'].",".(int)$searchParams['limit'];
                $limitOffset = (int)$searchParams['limitOffset'];
            } else {
                $Pagination  = new QUI\Controls\Navigating\Pagination($searchParams);
                $sqlParams   = $Pagination->getSQLParams();
                $sql         .= " LIMIT ".$sqlParams['limit'];
                $limitOffset = $Pagination->getStart();
            }
        } else {
            if (!$countOnly) {
                $sql         .= " LIMIT ".(int)20; // @todo: standard-limit als setting auslagern
                $limitOffset = 0;
            }
        }

//        if (!$countOnly) {
//            if (isset($searchParams['limit'])
//                && !empty($searchParams['limit'])
//                && isset($searchParams['sheet'])
//            ) {
//                $Pagination       = new QUI\Controls\Navigating\Pagination($searchParams);
//                $paginationParams = $Pagination->getSQLParams();
//                $queryLimit       = QUI\Database\DB::createQueryLimit($paginationParams['limit']);
//
//                foreach ($queryLimit['prepare'] as $bind => $value) {
//                    $binds[$bind] = [
//                        'value' => $value[0],
//                        'type'  => $value[1]
//                    ];
//                }
//
//                $sql .= " ".$queryLimit['limit'];
//            } elseif (isset($searchParams['limit'])) {
//                $queryLimit = QUI\Database\DB::createQueryLimit($searchParams['limit']);
//
//                foreach ($queryLimit['prepare'] as $bind => $value) {
//                    $binds[$bind] = [
//                        'value' => $value[0],
//                        'type'  => $value[1]
//                    ];
//                }
//
//                $sql .= " ".$queryLimit['limit'];
//            } else {
//                $sql .= " LIMIT 20"; // @todo as settings
//                $limitOffset = 0;
//            }
//        }

        $Stmt = $PDO->prepare($sql);

        // bind search values
        foreach ($binds as $var => $bind) {
            if (\strpos($var, ':') === false) {
                $var = ':'.$var;
            }

            $Stmt->bindValue($var, $bind['value'], $bind['type']);
        }

        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            if ($countOnly) {
                return 0;
            }

            return [];
        }

        $productIds      = [];
        $childrenRemoved = 0;

        // Sort by productNo
        if ($searchTerm) {
            $lettersSearch = preg_split("//u", $searchTerm, -1, PREG_SPLIT_NO_EMPTY);

            \usort($result, function ($a, $b) use ($lettersSearch) {
                $lettersA = preg_split("//u", $a['productNo'], -1, PREG_SPLIT_NO_EMPTY);
                $lettersB = preg_split("//u", $b['productNo'], -1, PREG_SPLIT_NO_EMPTY);
                $matchesA = count(\array_intersect($lettersSearch, $lettersA)) + \mb_strlen($a['productNo']);
                $matchesB = count(\array_intersect($lettersSearch, $lettersB)) + \mb_strlen($b['productNo']);

                if ($matchesA === $matchesB) {
                    return \strnatcmp($a['productNo'], $b['productNo']);
                }

                return $matchesA - $matchesB;
            });
        }

        foreach ($result as $k => $row) {
            if ($row['type'] === VariantChild::class) {
                if ($findVariantParentsByChildValues && !empty($row['parentId'])) {
                    $productIds[] = $row['parentId'];
                }

                // If children are to be ignored -> do NOT add them to the result list
                if ($this->ignoreVariantChildren) {
                    $childrenRemoved++;
                    continue;
                }
            }

            // If children are NOT to be ignored -> add them to the result list
            $productIds[] = $row['id'];
        }

        /**
         * If entries were removed from the result list repeat the search
         * and add as many entries as needed to fill the given limit with "normal" search results.
         */
        if ($childrenRemoved > 0) {
            if ($limitOffset !== false) {
                $searchParams['limitOffset'] = $limitOffset;
            }

            $searchParams['ignoreFindVariantParentsByChildValues'] = true;

//            if ($countOnly) {
//                return self::search($searchParams, $countOnly);
//            }

            $productIds = array_merge(
                $productIds,
                self::search($searchParams)
            );
        }

        $productIds = array_values(array_unique($productIds));

        if ($countOnly) {
            return count($productIds);
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
        try {
            $cname = 'products/search/frontend/searchfielddata/';
            $cname .= $this->Site->getId().'/';
            $cname .= $this->lang.'/';
            $cname .= $this->getGroupHashFromUser();
        } catch (QUI\Exception $Exception) {
            return [];
        }

        try {
            return SearchCache::get($cname);
        } catch (QUI\Exception $Exception) {
            // nothing, retrieve values
        }

        $searchFieldData = [];
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
            try {
                $Field = Fields::getField($fieldId);
            } catch (QUI\ERP\Products\Field\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
                continue;
            }

            if (!$this->canSearchField($Field)) {
                continue;
            }

            $searchFieldDataContent = [
                'id'          => $Field->getId(),
                'searchType'  => $Field->getSearchType(),
                'title'       => $Field->getTitle($Locale),
                'description' => $Field->getTitle($Locale)
            ];

            if (\in_array($Field->getSearchType(), $this->searchTypesWithValues)) {
                $searchValues = $this->getValuesFromField($Field, true, $catId);
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
     * @param array $options (optional) - Filter options
     * @return array
     */
    public function getSearchFields($options = [])
    {
        $searchFields         = [];
        $searchFieldsFromSite = $this->Site->getAttribute(
            'quiqqer.products.settings.searchFieldIds'
        );

        try {
            $eligibleFields = $this->getEligibleSearchFields();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
            $eligibleFields = [];
        }

        if (!$searchFieldsFromSite) {
            $searchFieldsFromSite = [];
        } else {
            $searchFieldsFromSite = \json_decode($searchFieldsFromSite, true);
        }

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($eligibleFields as $Field) {
            $available = true;

            if (!empty($options['showSearchableOnly']) && !$Field->isSearchable()) {
                $available = false;
            }

            if (!isset($searchFieldsFromSite[$Field->getId()])) {
                $available = false;
            }

            if ($available) {
                $searchFields[$Field->getId()] = \boolval(
                    $searchFieldsFromSite[$Field->getId()]
                );
            } else {
                $searchFields[$Field->getId()] = false;
            }
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
                $currentSearchFields[$fieldId] = \boolval($searchFields[$fieldId]);
            }
        }

        try {
            $Edit = $this->Site->getEdit();

            $Edit->setAttribute(
                'quiqqer.products.settings.searchFieldIds',
                \json_encode($currentSearchFields)
            );

            $Edit->save();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $currentSearchFields;
    }

    /**
     * Set the global default / fallback fields that are searchable
     *
     * @param array $searchFields
     * @return array - search fields
     */
    public static function setGlobalSearchFields($searchFields)
    {
        $GlobalSearch        = new QUI\ERP\Products\Search\GlobalFrontendSearch();
        $currentSearchFields = $GlobalSearch->getSearchFields();
        $newSearchFieldIds   = [];

        foreach ($currentSearchFields as $fieldId => $search) {
            if (isset($searchFields[$fieldId]) && $searchFields[$fieldId]) {
                $newSearchFieldIds[] = $fieldId;
            } else {
                unset($currentSearchFields[$fieldId]);
            }
        }

        $PackageCfg = QUI\ERP\Products\Utils\Package::getConfig();

        $PackageCfg->set(
            'search',
            'frontend',
            \implode(',', $newSearchFieldIds)
        );

        try {
            $PackageCfg->save();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }


        // field result
        $searchFields          = [];
        $PackageCfg            = QUI\ERP\Products\Utils\Package::getConfig();
        $searchFieldIdsFromCfg = $PackageCfg->get('search', 'frontend');

        if ($searchFieldIdsFromCfg === false) {
            $searchFieldIdsFromCfg = [];
        } else {
            $searchFieldIdsFromCfg = explode(',', $searchFieldIdsFromCfg);
        }

        try {
            $eligibleFields = $GlobalSearch->getEligibleSearchFields();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            $eligibleFields = [];
        }


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
        if (!\is_null($this->eligibleFields)) {
            return $this->eligibleFields;
        }

        switch ($this->siteType) {
            case self::SITETYPE_CATEGORY:
                $categoryId = $this->Site->getAttribute(
                    'quiqqer.products.settings.categoryId'
                );

                if ($categoryId === false || $categoryId === '') {
                    QUI::getMessagesHandler()->addAttention(
                        QUI::getLocale()->get(
                            'quiqqer/products',
                            'attention.frontendsearch.category.site.no.category',
                            [
                                'siteId' => $this->Site->getId()
                            ]
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
        if (\is_null($User)) {
            $User = QUI::getUserBySession();
        }

        $groups = $User->getGroups(false);

        if (\is_array($groups)) {
            $groups = \implode(',', $groups);
        } else {
            $groups = "";
        }

        return \md5($groups);
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
