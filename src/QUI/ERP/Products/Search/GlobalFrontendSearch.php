<?php

/**
 * This file contains QUI\ERP\Products\Search\GlobalFrontendSearch
 */

namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Products;

/**
 * Global, site-independent frontend search - searches ALL products
 *
 * @package QUI\ERP\Products\Search
 */
class GlobalFrontendSearch extends Search
{
    /**
     * Current search language
     *
     * @var string
     */
    protected $lang = null;

    /**
     * FrontendSearch constructor.
     *
     * @param string $lang (optional) - if omitted, use QUILocale::getCurrent()
     * @throws QUI\Exception
     */
    public function __construct($lang = null)
    {
        if (is_null($lang)) {
            $lang = QUI::getLocale()->getCurrent();
        }

        $this->lang = $lang;
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
     * @return array|int - product ids
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

        if (isset($searchParams['category']) && !empty($searchParams['category'])) {
            $where[]           = '`category` LIKE :category';
            $binds['category'] = array(
                'value' => '%,' . (int)$searchParams['category'] . ',%',
                'type'  => \PDO::PARAM_STR
            );
        }

        if (isset($searchParams['categories'])
            && !empty($searchParams['categories'])
            && is_array($searchParams['categories'])
        ) {
            $c               = 0;
            $whereCategories = array();

            foreach ($searchParams['categories'] as $categoryId) {
                $whereCategories[] = '`category` LIKE :category' . $c;

                $binds['category' . $c] = array(
                    'value' => '%,' . (int)$categoryId . ',%',
                    'type'  => \PDO::PARAM_STR
                );

                $c++;
            }

            // @todo das OR als setting (AND oder OR) (ist gedacht für die Navigation)
            $where[] = '(' . implode(' OR ', $whereCategories) . ')';
        }

        if (!isset($searchParams['fields']) && !isset($searchParams['freetext'])) {
            throw new Exception(
                'Wrong search parameters.',
                400
            );
        }

        // freetext search
        if (isset($searchParams['freetext']) && !empty($searchParams['freetext'])) {
            $whereFreeText = array();
            $value         = $this->sanitizeString($searchParams['freetext']);

            // split search value by space
            $freetextValues = explode(' ', $value);

            foreach ($freetextValues as $value) {
                // always search tags
                $whereFreeText[]       = '`tags` LIKE :freetextTags';
                $binds['freetextTags'] = array(
                    'value' => '%,' . $value . ',%',
                    'type'  => \PDO::PARAM_STR
                );

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

                    $whereFreeText[]              = '`' . $columnName . '` LIKE :freetext' . $fieldId;
                    $binds['freetext' . $fieldId] = array(
                        'value' => '%' . $value . '%',
                        'type'  => \PDO::PARAM_STR
                    );
                }
            }

            if (!empty($whereFreeText)) {
                $where[] = '(' . implode(' OR ', $whereFreeText) . ')';
            }
        }

        // tags search
        if (isset($searchParams['tags'])
            && !empty($searchParams['tags'])
            && is_array($searchParams['tags'])
        ) {
            $data = $this->getTagQuery($searchParams['tags']);

            if (!empty($data['where'])) {
                $where[] = $data['where'];
                $binds   = array_merge($binds, $data['binds']);
            }
        }

        // product permissions
        if (Products::usePermissions()) {
            // user
            $User = QUI::getUserBySession();

            $whereOr = array(
                '`viewUsersGroups` IS NULL'
            );

            if ($User->getId()) {
                $whereOr[] = '`viewUsersGroups` LIKE :permissionUser';

                $binds['permissionUser'] = array(
                    'value' => '%,u' . $User->getId() . ',%',
                    'type'  => \PDO::PARAM_STR
                );
            }

            // user groups
            $userGroupIds = $User->getGroups(false);
            $i            = 0;

            foreach ($userGroupIds as $groupId) {
                $whereOr[] = '`viewUsersGroups` LIKE :permissionGroup' . $i;

                $binds['permissionGroup' . $i] = array(
                    'value' => '%,g' . $groupId . ',%',
                    'type'  => \PDO::PARAM_STR
                );

                $i++;
            }

            $where[] = '(' . implode(' OR ', $whereOr) . ')';
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

        if (!$countOnly) {
            $sql .= " " . $this->validateOrderStatement($searchParams);
        }

        if (!$countOnly) {
            if (isset($searchParams['limit'])
                && !empty($searchParams['limit'])
                && isset($searchParams['sheet'])
            ) {
                $Pagination       = new QUI\Bricks\Controls\Pagination($searchParams);
                $paginationParams = $Pagination->getSQLParams();
                $queryLimit       = QUI\Database\DB::createQueryLimit($paginationParams['limit']);

                foreach ($queryLimit['prepare'] as $bind => $value) {
                    $binds[$bind] = array(
                        'value' => $value[0],
                        'type'  => $value[1]
                    );
                }

                $sql .= " " . $queryLimit['limit'];
            } elseif (isset($searchParams['limit'])) {
                $queryLimit = QUI\Database\DB::createQueryLimit($searchParams['limit']);

                foreach ($queryLimit['prepare'] as $bind => $value) {
                    $binds[$bind] = array(
                        'value' => $value[0],
                        'type'  => $value[1]
                    );
                }

                $sql .= " " . $queryLimit['limit'];
            } else {
                $sql .= " LIMIT 20"; // @todo as settings
            }
        }

//        if (strpos($sql, 'COUNT') === false) {
//            QUI\System\Log::writeRecursive($sql);
//        }

        $Stmt = $PDO->prepare($sql);

        // bind search values
        foreach ($binds as $var => $bind) {
            if (strpos($var, ':') === false) {
                $var = ':' . $var;
            }

            $Stmt->bindValue($var, $bind['value'], $bind['type']);
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
        return array();
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
        $searchFieldIdsFromCfg = $PackageCfg->get('search', 'freetext');

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
            'freetext',
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

        $fields               = Fields::getStandardFields();
        $this->eligibleFields = $this->filterEligibleSearchFields($fields);

        return $this->eligibleFields;
    }
}
