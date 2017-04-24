<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */

namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Utils\Tables;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Handler\Fields;

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
        $cname = 'products/search/backend/fieldvalues/';
        $cname .= $Field->getId() . '/';
        $cname .= $this->lang . '/';
        $cname .= $activeProductsOnly ? 'active' : 'inactive';

        try {
            return Cache::get($cname);
        } catch (QUI\Exception $Exception) {
            // nothing, retrieve values
        }

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
            $params['where']['category'] = array(
                'type'  => '%LIKE%',
                'value' => ',' . (int)$catId . ','
            );
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
                        switch ($Field->getId()) {
                            case Fields::FIELD_PRICE:
                                $params['select'] = array(
                                    'MIN(`minPrice`)',
                                    'MAX(`maxPrice`)'
                                );
                                break;

                            default:
                                $params['select'] = array(
                                    'MIN(`' . $column . '`)',
                                    'MAX(`' . $column . '`)'
                                );
                        }

                        break;
                }

                break;

            case SearchHandler::SEARCHDATATYPE_TEXT:
                switch ($Field->getSearchType()) {
                    case SearchHandler::SEARCHTYPE_SELECTSINGLE:
                    case SearchHandler::SEARCHTYPE_INPUTSELECTSINGLE:
                        $params['select'] = array('`' . $column . '`');
                        $params['group']  = $column;

                        break;

                    case SearchHandler::SEARCHTYPE_SELECTMULTI:
                        $params['select'] = array('`' . $column . '`');
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
                switch ($Field->getId()) {
                    case Fields::FIELD_PRICE:
                        $values = $Field->calculateValueRange(
                            $result[0]['MIN(`minPrice`)'],
                            $result[0]['MAX(`maxPrice`)']
                        );
                        break;

                    default:
                        $values = $Field->calculateValueRange(
                            $result[0]['MIN(`' . $column . '`)'],
                            $result[0]['MAX(`' . $column . '`)']
                        );
                }

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

        sort($values);

        Cache::set($cname, $values);

        return $values;
    }

    /**
     * Get where strings and binds with values and PDO datatypes
     *
     * @param array $fieldSearchData
     * @return array - where strings and binds with values and PDO datatypes
     * @throws QUI\Exception
     */
    protected function getFieldQueryData($fieldSearchData)
    {
        $where = array();
        $binds = array();

        foreach ($fieldSearchData as $fieldId => $value) {
            try {
                $Field = Fields::getField($fieldId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning(
                    'Product Search :: could not build query data for field #'
                    . $fieldId . ' -> ' . $Exception->getMessage()
                );

                continue;
            }

            // if field is not searchable -> ignore in search
            if (!$this->canSearchField($Field)) {
                continue;
            }

            // wenn feld -> price feld
            // scheiss vorgehensweise, wollen aber kein doppelten code
            if (get_class($this) == FrontendSearch::class) {
                $User = QUI::getUserBySession();

                if (!QUI\ERP\Utils\User::isNettoUser($User)
                    && $Field->getType() == Fields::TYPE_PRICE
                ) {
                    $Tax  = QUI\ERP\Tax\Utils::getTaxByUser(QUI::getUserBySession());
                    $calc = ($Tax->getValue() + 100) / 100;

                    // calc netto sum
                    if (is_array($value)
                        && isset($value['from'])
                        && isset($value['to'])
                        && $calc
                    ) {
                        $value['from'] = $value['from'] / $calc;
                        $value['to']   = $value['to'] / $calc;
                    }
                }
            }


            $columnName = SearchHandler::getSearchFieldColumnName($Field);
            $column     = '`' . $columnName . '`';

            switch ($Field->getSearchType()) {
                case SearchHandler::SEARCHTYPE_HASVALUE:
                    if (boolval($value)) {
                        $where[] = $column . ' IS NOT NULL';
                    } else {
                        $where[] = $column . ' IS NULL';
                    }
                    break;

                case SearchHandler::SEARCHTYPE_BOOL:
                    if (boolval($value)) {
                        $where[] = $column . ' = 1';
                    } else {
                        $where[] = $column . ' = 0';
                    }
                    break;

                case SearchHandler::SEARCHTYPE_SELECTSINGLE:
                case SearchHandler::SEARCHTYPE_INPUTSELECTSINGLE:
                    if (empty($value)) {
                        continue;
                    }

                    if (!is_string($value)) {
                        throw new Exception(array(
                            'quiqqer/products',
                            'exception.search.value.invalid',
                            array(
                                'fieldId'    => $Field->getId(),
                                'fieldTitle' => $Field->getTitle()
                            )
                        ));
                    }

                    $where[]            = $column . ' = :' . $columnName;
                    $binds[$columnName] = array(
                        'value' => $this->sanitizeString($value),
                        'type'  => \PDO::PARAM_STR
                    );
                    break;

                case SearchHandler::SEARCHTYPE_SELECTRANGE:
                case SearchHandler::SEARCHTYPE_INPUTSELECTRANGE:
                    if (empty($value)) {
                        continue;
                    }

                    if (!is_array($value)) {
                        throw new Exception(array(
                            'quiqqer/products',
                            'exception.search.value.invalid',
                            array(
                                'fieldId'    => $Field->getId(),
                                'fieldTitle' => $Field->getTitle()
                            )
                        ));
                    }

                    $from = false;
                    $to   = false;

                    if (isset($value['from']) && !empty($value['from'])) {
                        $from = $value['from'];

                        if (!is_string($from) && !is_numeric($from)) {
                            throw new Exception(array(
                                'quiqqer/products',
                                'exception.search.value.invalid',
                                array(
                                    'fieldId'    => $Field->getId(),
                                    'fieldTitle' => $Field->getTitle()
                                )
                            ));
                        }
                    }

                    if (isset($value['to']) && !empty($value['to'])) {
                        $to = $value['to'];

                        if (!is_string($to) && !is_numeric($to)) {
                            throw new Exception(array(
                                'quiqqer/products',
                                'exception.search.value.invalid',
                                array(
                                    'fieldId'    => $Field->getId(),
                                    'fieldTitle' => $Field->getTitle()
                                )
                            ));
                        }
                    }

                    if ($from !== false && $to !== false) {
                        if ($from > $to) {
                            $_from = $from;
                            $from  = $to;
                            $to    = $_from;
                        }
                    }

                    $where = array();

                    if ($from !== false) {
                        $where[] = $column . ' >= :' . $columnName . 'From';

                        $binds[$columnName . 'From'] = array(
                            'value' => $this->sanitizeString($from),
                            'type'  => \PDO::PARAM_INT
                        );
                    }

                    if ($to !== false) {
                        $where[] = $column . ' <= :' . $columnName . 'To';

                        $binds[$columnName . 'To'] = array(
                            'value' => $this->sanitizeString($to),
                            'type'  => \PDO::PARAM_INT
                        );
                    }
                    break;

                case SearchHandler::SEARCHTYPE_DATERANGE:
                    if (empty($value)) {
                        continue;
                    }

                    if (!is_array($value)) {
                        throw new Exception(array(
                            'quiqqer/products',
                            'exception.search.value.invalid',
                            array(
                                'fieldId'    => $Field->getId(),
                                'fieldTitle' => $Field->getTitle()
                            )
                        ));
                    }

                    $from = false;
                    $to   = false;

                    if (isset($value['from']) && !empty($value['from'])) {
                        $from = $value['from'];

                        if (!is_numeric($from)) {
                            throw new Exception(array(
                                'quiqqer/products',
                                'exception.search.value.invalid',
                                array(
                                    'fieldId'    => $Field->getId(),
                                    'fieldTitle' => $Field->getTitle()
                                )
                            ));
                        }
                    }

                    if (isset($value['to']) && !empty($value['to'])) {
                        $to = $value['to'];

                        if (!is_numeric($from)) {
                            throw new Exception(array(
                                'quiqqer/products',
                                'exception.search.value.invalid',
                                array(
                                    'fieldId'    => $Field->getId(),
                                    'fieldTitle' => $Field->getTitle()
                                )
                            ));
                        }
                    }

                    if ($from !== false && $to !== false) {
                        if ($from > $to) {
                            $_from = $from;
                            $from  = $to;
                            $to    = $_from;
                        }
                    }

                    $where = array();

                    if ($from !== false) {
                        $where[]                     = $column . ' >= :' . $columnName . 'From';
                        $binds[$columnName . 'From'] = array(
                            'value' => (int)$value,
                            'type'  => \PDO::PARAM_INT
                        );
                    }

                    if ($to !== false) {
                        $where[]                   = $column . ' <= :' . $columnName . 'To';
                        $binds[$columnName . 'To'] = array(
                            'value' => (int)$value,
                            'type'  => \PDO::PARAM_INT
                        );
                    }
                    break;

                case SearchHandler::SEARCHTYPE_DATE:
                    if (empty($value)) {
                        continue;
                    }

                    if (!is_string($value) && !is_numeric($value)) {
                        throw new Exception(array(
                            'quiqqer/products',
                            'exception.search.value.invalid',
                            array(
                                'fieldId'    => $Field->getId(),
                                'fieldTitle' => $Field->getTitle()
                            )
                        ));
                    }

                    $where              = $column . ' = :' . $columnName;
                    $binds[$columnName] = array(
                        'value' => (int)$value,
                        'type'  => \PDO::PARAM_INT
                    );
                    break;

                case SearchHandler::SEARCHTYPE_SELECTMULTI:
                    if (empty($value)) {
                        continue;
                    }

                    if (!is_array($value)) {
                        throw new Exception(array(
                            'quiqqer/products',
                            'exception.search.value.invalid',
                            array(
                                'fieldId'    => $Field->getId(),
                                'fieldTitle' => $Field->getTitle()
                            )
                        ));
                    }

                    for ($i = 0; $i < count($value); $i++) {
                        $where[]                 = $column . ' = :' . $columnName . $i;
                        $binds[$columnName . $i] = array(
                            'value' => $this->sanitizeString($value),
                            'type'  => \PDO::PARAM_STR
                        );
                    }
                    break;

                case SearchHandler::SEARCHTYPE_TEXT:
                    if (empty($value)) {
                        continue;
                    }

                    if (!is_string($value)) {
                        throw new Exception(array(
                            'quiqqer/products',
                            'exception.search.value.invalid',
                            array(
                                'fieldId'    => $Field->getId(),
                                'fieldTitle' => $Field->getTitle()
                            )
                        ));
                    }

                    $where[]            = $column . ' LIKE :' . $columnName;
                    $binds[$columnName] = array(
                        'value' => '%' . $this->sanitizeString($value) . '%',
                        'type'  => \PDO::PARAM_STR
                    );
                    break;

                default:
                    throw new Exception(array(
                        'quiqqer/products',
                        'exception.search.field.unknown.searchtype',
                        array(
                            'fieldId'    => $Field->getId(),
                            'fieldTitle' => $Field->getTitle()
                        )
                    ));
            }
        }

        return array(
            'where' => $where,
            'binds' => $binds
        );
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
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        // calculate group hash
        $userGroups = $User->getGroups(false);
        sort($userGroups);

        $groupHash = md5(implode('', $userGroups));
        $cName     = 'products/search/userfieldids/' . $Field->getId() . '/' . $groupHash;

        try {
            return Cache::get($cName);
        } catch (\Exception $Exception) {
            // build cache entry
        }

        $canSearch = false;

        if ($Field->isPublic()) {
            $canSearch = true;
        } elseif ($Field->hasViewPermission($User)) {
            $canSearch = true;
        } else {
            $eligibleFields = $this->getEligibleSearchFields();

            foreach ($eligibleFields as $EligibleField) {
                if ($Field->getId() === $EligibleField->getId()) {
                    $canSearch = true;
                    break;
                }
            }
        }

        Cache::set($cName, $canSearch);

        return $canSearch;
    }

    /**
     * Sanitizes a string so it can be used for search
     *
     * @param string $str
     * @return false|string
     */
    protected function sanitizeString($str)
    {
        if (!is_string($str) && !is_numeric($str)) {
            return false;
        }

//        $str = Orthos::removeHTML($str);
//        $str = Orthos::clearPath($str);
//        $str = htmlspecialchars_decode($str);
//        $str = str_replace(
//            array(
//                '<',
//                '%3C',
//                '>',
//                '%3E',
//                '"',
//                '%22',
////                '\\',
////                '%5C',
////                '/',
////                '%2F',
//                '\'',
//                '%27',
//            ),
//            '',
//            $str
//        );
//        $str = htmlspecialchars($str);

        return $str;
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
            if ($Field->isSearchable() && $Field->getSearchType()) {
                $eligibleFields[] = $Field;
            }
        }

        return $eligibleFields;
    }

    /**
     * Validates an order statement
     *
     * @param array $searchParams - search params
     * @return string - valid order statement
     */
    protected function validateOrderStatement($searchParams)
    {
        $order = 'ORDER BY';

        if (!isset($searchParams['sortOn']) || empty($searchParams['sortOn'])) {
            $order .= ' F' . Fields::FIELD_PRIORITY . ' ASC';
            return $order;
        }

        // check special fields
        switch ($searchParams['sortOn']) {
            case 'id':
            case 'title':
            case 'productNo':
            case 'category':
            case 'active':
            case 'lang':
            case 'tags':
            case 'c_date':
            case 'e_date':
                $order .= ' ' . $searchParams['sortOn'];
                break;

            case 'priority':
                $order .= ' F' . Fields::FIELD_PRIORITY;
                break;

            default:
                if (mb_strpos($searchParams['sortOn'], 'F') === 0) {
                    $searchParams['sortOn'] = mb_substr($searchParams['sortOn'], 1);
                }

                $orderFieldId = (int)$searchParams['sortOn'];

                try {
                    $OrderField = Fields::getField($orderFieldId);

                    if (!$this->canSearchField($OrderField)) {
                        throw new QUI\Exception();
                    }

                    $order .= ' ' . SearchHandler::getSearchFieldColumnName($OrderField);
                } catch (\Exception $Exception) {
                    // if field does not exist or throws some other kind of error - it is not searchable
                    $order .= ' F' . Fields::FIELD_PRIORITY;
                    return $order;
                }
        }

        if (!isset($searchParams['sortBy']) || empty($searchParams['sortBy'])) {
            $order .= ' ASC';
            return $order;
        }

        switch ($searchParams['sortBy']) {
            case 'ASC':
            case 'DESC':
                $order .= " " . $searchParams['sortBy'];
                break;

            default:
                $order .= " ASC";
        }

        return $order;
    }
    
    /**
     * Build the query for the tag groups
     *
     * @param array $tags
     * @return array
     */
    protected function getTagQuery(array $tags)
    {
        $Tags = new QUI\Tags\Manager(QUI::getRewrite()->getProject());
        $list = array();

        foreach ($tags as $tag) {
            $groups = $Tags->getGroupsFromTag($tag);

            foreach ($groups as $group) {
                $list[$group['id']][] = $tag;
            }
        }

        $binds       = array();
        $whereGroups = array();

        $i = 0;

        foreach ($list as $group => $tags) {
            $tagList = array();

            foreach ($tags as $tag) {
                $tagList[] = '`tags` LIKE :tag' . $i;

                $binds['tag' . $i] = array(
                    'value' => '%,' . $tag . ',%',
                    'type'  => \PDO::PARAM_STR
                );

                $i++;
            }

            $whereGroups[] = '(' . implode(' OR ', $tagList) . ')';
        }


        $where = '(' . implode(' AND ', $whereGroups) . ')';

        return array(
            'where' => $where,
            'binds' => $binds
        );
    }
}
