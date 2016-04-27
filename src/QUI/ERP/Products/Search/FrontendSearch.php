<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Search;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Search\Cache as SearchCache;
use QUI\ERP\Products\Utils\Tables as TablesUtils;

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
     * @throws QUI\Exception
     */
    public function __construct($Site)
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
     * Execute product search
     *
     * @param array $searchParams - search parameters
     * @param bool $countOnly (optional) - return count of search results only [default: false]
     * @return array - product ids
     * @throws QUI\Exception
     */
    public function search($searchParams, $countOnly = false)
    {
        $PDO = QUI::getDataBase()->getPDO();

        $binds = array();
        $where = array();

        if ($countOnly) {
            $sql = "SELECT COUNT(*)";
        } else {
            $sql = "SELECT id";
        }

        $sql .= " FROM " . TablesUtils::getProductCacheTableName();

        if (isset($searchData['category']) &&
            !empty($searchData['category'])
        ) {
            $where = array(
                'category = :category'
            );

            $binds = array(
                'category' => array(
                    'value' => (int)$searchData['category'],
                    'type'  => \PDO::PARAM_INT
                )
            );
        }

        if (!isset($searchData['fields'])) {
            throw new QUI\Exception(
                'Wrong search parameters.',
                400
            );
        }

        foreach ($searchData['fields'] as $fieldId => $data) {
            if (empty($data)) {
                continue;
            }

            if (isset($searchData['category_id'])
                && !empty($searchData['category_id'])
            ) {
                if (!self::canSearchField($fieldId,
                    $searchData['category_id'])
                ) {
                    continue;
                }
            } else {
                if (!self::canSearchField($fieldId)) {
                    continue;
                }
            }

            $field = 'hklfield_' . $fieldId;

            // single value search type
            if (isset($data['like'])) {
                if (empty($data['like'])) {
                    continue;
                }

                $where[] = $field . ' LIKE :' . $field;
                $Field   = CategoryManager::getCategoryField($fieldId);

                $binds[$field] = array(
                    'value' => "%" . $Field->validate($data['like']) . "%",
                    'type'  => \PDO::PARAM_STR
                );

                continue;
            }

            // hasValue search type
            if (isset($data['hasValue'])) {
                if ($data['hasValue']) {
                    $where[] = $field . ' IS NOT NULL';
                } else {
                    $where[] = $field . ' IS NULL';
                }

                continue;
            }

            // single value search type
            if (isset($data['value'])) {
                // if empty assume false?
                if ($data['value'] === false) {
                    $where[]       = $field . ' = :' . $field;
                    $binds[$field] = array(
                        'value' => 0,
                        'type'  => \PDO::PARAM_INT
                    );
                } elseif ($data['value'] === true) {
                    $where[]       = $field . ' = :' . $field;
                    $binds[$field] = array(
                        'value' => 1,
                        'type'  => \PDO::PARAM_INT
                    );
                } else {
                    $Field = CategoryManager::getCategoryField($fieldId);

                    $where[]       = $field . ' = :' . $field;
                    $binds[$field] = array(
                        'value' => $Field->validate($data['value']),
                        'type'  => \PDO::PARAM_STR
                    );
                }

                continue;
            }

            try {
                $Field = new CategoryField($fieldId);
            } catch (QUI\Exception $Exception) {
                \QUI\System\Log::addWarning(
                    'MachineSearch :: search -> '
                    . $Exception->getMessage()
                );
                continue;
            }

            $from = false;
            $to   = false;

            if (isset($data['from'])) {
                $from = $Field->validate($data['from']);
            }

            if (isset($data['to'])) {
                $to = $Field->validate($data['to']);
            }

            if ($from !== false && $to !== false) {
                if ($from > $to) {
                    $_from = $from;
                    $from  = $to;
                    $to    = $_from;
                }
            }

            if ($from !== false) {
                $where[]                = $field . ' >= :' . $field . 'From';
                $binds[$field . 'From'] = array(
                    'value' => $from,
                    'type'  => \PDO::PARAM_INT
                );
            }

            if ($to !== false) {
                $where[]              = $field . ' <= :' . $field . 'To';
                $binds[$field . 'To'] = array(
                    'value' => $to,
                    'type'  => \PDO::PARAM_INT
                );
            }
        }

        $sql .= " WHERE publish = 1";

        if (!empty($where)) {
            $sql .= " AND " . implode(" AND ", $where);
        }

        if (isset($searchData['sortOn']) &&
            !empty($searchData['sortOn'])
        ) {
            $order = "ORDER BY " . QUI\Utils\Security\Orthos::clear(
                    $searchData['sortOn']
                );

            if (isset($searchData['sortBy']) &&
                !empty($searchData['sortBy'])
            ) {
                $order .= " " . QUI\Utils\Security\Orthos::clear(
                        $searchData['sortBy']
                    );
            } else {
                $order .= " ASC";
            }

            $sql .= " " . $order;
            $sql .= ", hklfield_" . CategoryManager::FIELD_MANUFACTURER . " ASC";
            $sql .= ", hklfield_15 ASC";
            $sql .= ", hklfield_" . CategoryManager::FIELD_TYPE . " ASC";
        } else {
            /**
             * Spezialsortierung auf Wunsch von HKL
             *
             * s. https://dev.quiqqer.com/hklused/machines/issues/76
             * @hardcoded field id für "Gesamtgewicht"
             */
            $sql .= " ORDER BY hklfield_" . CategoryManager::FIELD_MANUFACTURER . " ASC";
            $sql .= ", hklfield_15 ASC";
            $sql .= ", hklfield_" . CategoryManager::FIELD_TYPE . " ASC";
        }

        // do not set limit if count is requested (for pagination purposes)
        if (!$countOnly && !$noLimit) {
            if (isset($searchData['limit']) &&
                !empty($searchData['limit'])
            ) {
                $Pagination = new QUI\Bricks\Controls\Pagination($searchData);
                $sqlParams  = $Pagination->getSQLParams();
                $sql .= " LIMIT " . $sqlParams['limit'];
            } else {
                $limit = QUI::getConfig(\Hklused\Machines\Utils::CONFIG_FILE)->get(
                    "site",
                    "default_machinecount"
                );

                if (!empty($limit)) {
                    $sql .= " LIMIT " . (int)$limit;
                }
            }
        }

        $Stmt = $PDO->prepare($sql);

        foreach ($binds as $var => $bind) {
            $Stmt->bindValue(':' . $var, $bind['value'], $bind['type']);
        }

        try {
            $Stmt->execute();
            $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $Exception) {
            if ($countOnly) {
                return 0;
            }

            return array();
        }

        if ($countOnly) {
            return (int)current(current($result));
        }

        $machineIds = array();

        foreach ($result as $row) {
            $machineIds[] = $row['machine_id'];
        }

        return $machineIds;
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
}