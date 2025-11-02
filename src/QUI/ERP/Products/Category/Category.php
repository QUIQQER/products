<?php

/**
 * This file contains QUI\ERP\Products\Category\Model
 */

namespace QUI\ERP\Products\Category;

use QUI;
use QUI\Database\Exception;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ExceptionStack;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Projects\Project;

use function array_column;
use function array_key_exists;
use function array_merge;
use function array_reverse;
use function array_shift;
use function class_exists;
use function defined;
use function ini_get;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function set_time_limit;
use function usort;

/**
 * Class Category
 * - Category Model
 *
 * @package QUI\ERP\Products\Category
 *
 * @example
 * QUI\ERP\Products\Handler\Categories::getCategory( ID );
 */
class Category extends QUI\QDOM implements QUI\ERP\Products\Interfaces\CategoryInterface
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected int $id;

    /**
     * Parent-ID
     *
     * @var integer
     */
    protected int $parentId;

    /**
     * @var ?array
     */
    protected ?array $fields = null;

    /**
     * @var ?array
     */
    protected ?array $sites = null;

    /**
     * @var array|null
     */
    protected array | null $defaultSites = [];

    /**
     * db data
     * @var array
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $caches = [];

    /**
     * @var array
     */
    protected mixed $customData = [];

    /**
     * Model constructor.
     *
     * @param integer $categoryId
     * @param array $data - optional, category data
     */
    public function __construct(int $categoryId, array $data)
    {
        $this->parentId = 0;
        $this->id = $categoryId;
        $this->data = $data;

        $this->caches = [
            'site-binds'
        ];

        if (isset($data['parentId'])) {
            $this->parentId = (int)$data['parentId'];
        }

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }

        if (!empty($data['custom_data'])) {
            $this->customData = json_decode($data['custom_data'], true);
        }
    }

    /**
     * Return the title / name of the category
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getTitle($Locale = null): string
    {
        if (!$Locale) {
            return QUI::getLocale()->get(
                'quiqqer/products',
                'products.category.' . $this->getId() . '.title'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.category.' . $this->getId() . '.title'
        );
    }

    /**
     * Return the title / name of the category
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getDescription(null | QUI\Locale $Locale = null): string
    {
        if (!$Locale) {
            return QUI::getLocale()->get(
                'quiqqer/products',
                'products.category.' . $this->getId() . '.description'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.category.' . $this->getId() . '.description'
        );
    }

    /**
     * Return Field-ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Return category url
     * Return the category url of a bound site from the project
     *
     * @param Project|null $Project - optional, default = global project
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrl(null | Project $Project = null): string
    {
        if (!$Project) {
            $Project = QUI::getRewrite()->getProject();
        }

        try {
            $Site = $this->getSite($Project);

            return $Site->getUrlRewritten();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        return '';
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getPath(null | QUI\Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $parents = $this->getParents();
        $parents = array_reverse($parents);
        $path = '/';

        array_shift($parents);

        foreach ($parents as $Parent) {
            $path .= $Parent->getTitle($Locale) . '/';
        }

        return $path;
    }

    /**
     * @return QUI\ERP\Products\Interfaces\CategoryInterface[]
     */
    public function getParents(): array
    {
        $parents = [];

        try {
            $Parent = $this->getParent();

            if ($Parent) {
                $parents[] = $Parent;
            }
        } catch (QUI\Exception) {
            return $parents;
        }

        while ($Parent) {
            try {
                $Parent = $Parent->getParent();

                if (!$Parent) {
                    break;
                }

                $parents[] = $Parent;
            } catch (QUI\Exception) {
                break;
            }
        }

        return $parents;
    }

    /**
     * Return the ID of the parent category
     * - Category 0 has no parent => returns false
     *
     * @return integer|boolean
     */
    public function getParentId(): bool | int
    {
        if ($this->getId() === 0) {
            return false;
        }

        return $this->parentId;
    }

    /**
     * Set a new parent to the category
     *
     * @param integer $parentId
     * @throws QUI\Exception
     */
    public function setParentId(int $parentId): void
    {
        if ($parentId == $this->getId()) {
            return;
        }

        // exists the category?
        if ($parentId !== 0) {
            Categories::getCategory($parentId);
        }

        $this->parentId = $parentId;
    }

    /**
     * Return the parent category
     * - Category 0 has no parent => returns false
     *
     * @return bool|QUI\ERP\Products\Interfaces\CategoryInterface
     * @throws QUI\Exception
     */
    public function getParent(): bool | QUI\ERP\Products\Interfaces\CategoryInterface
    {
        if ($this->getId() === 0) {
            return false;
        }

        return Categories::getCategory($this->parentId);
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $cacheName = Categories::getCacheName($this->getId()) . '/attributes';
        $cacheFields = Categories::getCacheName($this->getId()) . '/fields';

        try {
            $fields = QUI\Cache\LongTermCache::get($cacheFields);
        } catch (QUI\Cache\Exception) {
            $fields = [];
            $fieldList = $this->getFields();

            /* @var $Field QUI\ERP\Products\Field\Field */
            foreach ($fieldList as $Field) {
                $fields[] = $Field->getAttributes();
            }

            QUI\Cache\LongTermCache::set($cacheFields, $fields);
        }

        try {
            $attributes = QUI\Cache\LongTermCache::get($cacheName);
        } catch (QUI\Cache\Exception) {
            $attributes = parent::getAttributes();
            $attributes['id'] = $this->getId();
            $attributes['parent'] = $this->getParentId();

            QUI\Cache\LongTermCache::set($cacheName, $attributes);
        }

        $attributes['title'] = $this->getTitle();
        $attributes['description'] = $this->getDescription();
        $attributes['fields'] = $fields;
        $attributes['custom_data'] = $this->getCustomData();

        return $attributes;
    }

    /**
     * @return ViewFrontend|ViewBackend
     */
    public function getView(): ViewBackend | ViewFrontend
    {
        switch ($this->getAttribute('viewType')) {
            case 'backend':
                return $this->getViewBackend();

            default:
                return $this->getViewFrontend();
        }
    }

    /**
     * @return ViewFrontend
     */
    public function getViewFrontend(): ViewFrontend
    {
        return new ViewFrontend($this);
    }

    /**
     * @return ViewBackend
     */
    public function getViewBackend(): ViewBackend
    {
        return new ViewBackend($this);
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return Categories::getCategories([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);
    }

    /**
     * Return the number of the children
     *
     * @return integer
     */
    public function countChildren(): int
    {
        try {
            $data = QUI::getDataBase()->fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                'count' => [
                    'select' => 'id',
                    'as' => 'id'
                ],
                'where' => [
                    'parentId' => $this->getId()
                ]
            ]);
        } catch (QUI\Database\Exception) {
            return 0;
        }

        if (isset($data[0]['id'])) {
            return (int)$data[0]['id'];
        }

        return 0;
    }

    /**
     * Return the category site
     *
     * @param QUI\Projects\Project|null $Project
     * @return QUI\Interfaces\Projects\Site
     *
     * @throws QUI\Exception
     */
    public function getSite(null | Project $Project = null): QUI\Interfaces\Projects\Site
    {
        if (!$Project) {
            $Project = QUI::getRewrite()->getProject();
        }

        $defaults = $this->defaultSites;
        $name = $Project->getName();
        $lang = $Project->getLang();

        if (isset($defaults[$name][$lang])) {
            return $defaults[$name][$lang];
        }

        $cacheName = 'products/category/' . $this->getId();
        $cacheName .= '/site';
        $cacheName .= '/' . $Project->getName();
        $cacheName .= '/' . $Project->getLang();

        try {
            $siteParams = QUI\Cache\LongTermCache::get($cacheName);
            $Site = $Project->get($siteParams['id']);
        } catch (QUI\Exception) {
            $sites = $this->getSites($Project);

            if (isset($sites[0])) {
                $Site = $sites[0];
            } else {
                QUI\System\Log::addWarning(
                    QUI::getLocale()->get('quiqqer/products', 'exception.category.has.no.site', [
                        'id' => $this->getId(),
                        'title' => $this->getTitle()
                    ])
                );

                $Site = $Project->firstChild();
            }

            QUI\Cache\LongTermCache::set($cacheName, [
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'id' => $Site->getId()
            ]);
        }

        return $Site;
    }

    /**
     * Return all sites which assigned the category
     *
     * @param QUI\Projects\Project|null $Project
     * @return QUI\Projects\Site[]
     *
     * @throws QUI\Exception
     */
    public function getSites($Project = null): array
    {
        if ($this->sites !== null && !$Project) {
            return $this->sites;
        }

        if ($this->sites == null) {
            $this->refreshSiteBinds();
        }

        if (!$Project) {
            return $this->sites;
        }

        $sites = [];
        $id = $this->getId();
        $result = $this->sites;

        $projectName = $Project->getName();
        $projectLang = $Project->getLang();

        foreach ($result as $Site) {
            /* @var $Site QUI\Projects\Site */
            if ($Site->getProject()->getName() != $projectName) {
                continue;
            }

            if ($Site->getProject()->getLang() != $projectLang) {
                continue;
            }

            if (
                $Site->getAttribute('quiqqer.products.settings.categoryId') == $id ||
                $Site->getAttribute('quiqqer.products.settings.categoryId') == 0
            ) {
                $sites[] = $Site;
            }
        }

        usort($sites, function ($SiteA, $SiteB) {
            /* @var $SiteA QUI\Projects\Site */
            /* @var $SiteB QUI\Projects\Site */
            $a = $SiteA->getAttribute('quiqqer.products.settings.categoryId');
            $b = $SiteB->getAttribute('quiqqer.products.settings.categoryId');

            if ($a == $b) {
                return 0;
            }

            return ($a > $b) ? -1 : 1;
        });

        return $sites;
    }

    /**
     * refresh the internal sites bind
     *
     * @return array|null
     *
     * @throws Exception
     * @throws QUI\Exception
     */
    public function refreshSiteBinds(): ?array
    {
        try {
            $result = [];
            $cache = QUI\Cache\LongTermCache::get($this->getSiteCacheName());

            foreach ($cache as $siteUrl) {
                try {
                    $result[] = QUI\Projects\Site\Utils::getSiteByLink($siteUrl);
                } catch (QUI\Exception) {
                }
            }

            $this->sites = $result;

            return $result;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        // must be cached or set it at the site save event
        $projects = QUI::getProjectManager()->getProjectList();
        $sites = [];
        $id = $this->getId();

        foreach ($projects as $Project) {
            $projectName = $Project->getName();
            $projectLang = $Project->getLang();

            // Fetch sites directly via db for performance reasons
            $sql = "SELECT `id` FROM `" . $Project->table() . "`";
            $sql .= " WHERE `active` = 1";
            $sql .= " AND (`extra` LIKE '%\"quiqqer.products.settings.categoryId\":\"" . $id . "\"%'";
            $sql .= " OR `extra` LIKE '%\"quiqqer.products.settings.categoryId\":" . $id . "%')";

            $result = QUI::getDataBase()->fetchSQL($sql);
            $idList = array_column($result, 'id');

            foreach ($result as $row) {
                $siteId = $row['id'];
                $sites[] = $Project->get($siteId);
            }

            if (!isset($this->data['sites']) || is_string($this->data['sites'])) {
                $this->data['sites'] = [];
            }

            $this->data['sites'][$projectName][$projectLang] = $idList;
        }

        $this->sites = $sites;

        // caching
        $cache = [];

        foreach ($this->sites as $Site) {
            $cache[] = $Site->getUrl();
        }

        QUI\Cache\LongTermCache::set($this->getSiteCacheName(), $cache);


        return $this->sites;
    }

    /**
     * Products
     */

    /**
     * Return all products from the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['limit']
     *                              $queryParams['order']
     *                              $queryParams['debug']
     * @return array
     */
    public function getProducts(array $params = []): array
    {
        $query = [
            'limit' => 20
        ];

        $where = [
            'categories' => [
                'type' => '%LIKE%',
                'value' => ',' . $this->getId() . ','
            ]
        ];

        if (isset($params['where'])) {
            $where = array_merge($where, $params['where']);
        }

        $query['where'] = $where;

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['debug'])) {
            $query['debug'] = $params['debug'];
        }

        return Products::getProducts($query);
    }

    /**
     * Return all product ids from the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['limit']
     *                              $queryParams['order']
     *                              $queryParams['debug']
     * @return array
     */
    public function getProductIds(array $params = []): array
    {
        $query = [];

        $where = [
            'categories' => [
                'type' => '%LIKE%',
                'value' => ',' . $this->getId() . ','
            ]
        ];

        if (isset($params['where'])) {
            $where = array_merge($where, $params['where']);
        }

        $query['where'] = $where;

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['debug'])) {
            $query['debug'] = $params['debug'];
        }

        return Products::getProductIds($query);
    }

    /**
     * Return the number of the products in the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['debug']
     * @return integer
     */
    public function countProducts(array $params = []): int
    {
        $query = [];

        $where = [
            'categories' => [
                'type' => '%LIKE%',
                'value' => ',' . $this->getId() . ','
            ]
        ];

        if (isset($params['where'])) {
            $where = array_merge($where, $params['where']);
        }

        $query['where'] = $where;

        if (isset($params['debug'])) {
            $query['debug'] = $params['debug'];
        }

        return Products::countProducts($query);
    }

    /**
     * Set all field settings to all products in the category
     *
     * @throws QUI\ExceptionStack
     * @throws QUI\Exception
     *
     * @todo auslagern auf queue, wenn queue service existiert
     * @todo ansonsten über junks aufbauen
     * @todo vorsicht wegen timeouts bei 10.000 produkten
     */
    public function setFieldsToAllProducts(): void
    {
        $productIds = $this->getProductIds();
        $fields = $this->getFields();
        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($productIds as $productId) {
            if (!DEVELOPMENT) { // @phpstan-ignore-line
                set_time_limit(3);
            }

            try {
                $Product = Products::getProduct($productId);

                foreach ($fields as $Field) {
                    $Product->addField($Field);
                }

                $Product->save();
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        // reset time limit
        set_time_limit((int)ini_get('max_execution_time'));

        QUI::getEvents()->fireEvent(
            'onQuiqqerProductsCategorySetFieldsToAllProducts',
            [$this]
        );

        if (!$ExceptionStack->isEmpty()) {
            throw $ExceptionStack;
        }
    }

    /**
     * Fields
     */

    /**
     * Return the category fields
     *
     * @return array
     */
    public function getFields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }


        $fields = [];
        $fieldCheck = [];

        $data = $this->data;
        $standardFields = Fields::getStandardFields();

//        $isFieldInArray = function ($Field, $array = []) {
//            /* @var QUI\ERP\Products\Field\Field $Field */
//            /* @var QUI\ERP\Products\Field\Field $Entry */
//            foreach ($array as $Entry) {
//                if ($Entry->getId() == $Field->getId()) {
//                    return true;
//                }
//            }
//
//            return false;
//        };

        if (isset($data['fields'])) {
            $jsonData = json_decode($data['fields'], true);

            if (!is_array($jsonData)) {
                $jsonData = [];
            }

            foreach ($jsonData as $field) {
                try {
                    $Field = Fields::getField($field['id']);
                    $Field->setAttribute('publicStatus', $field['publicStatus']);
                    $Field->setAttribute('searchStatus', $field['searchStatus']);

                    if (!empty($field['options'])) {
                        $Field->setOptions($field['options']);
                    }

                    $fields[] = $Field;

                    $fieldCheck[$Field->getId()] = true;
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException(
                        $Exception,
                        QUI\System\Log::LEVEL_DEBUG
                    );
                }
            }
        }

        // add standard fields to the array
        foreach ($standardFields as $Field) {
            $fieldId = $Field->getId();

            if (!isset($fieldCheck[$fieldId])) {
                $fields[] = $Field;
                $fieldCheck[$fieldId] = true;
            }
        }

        $this->fields = $fields;

        return $this->fields;
    }

    /**
     * Add a field to the category
     *
     * @param QUI\ERP\Products\Field\Field $Field
     *
     * @throws QUI\Exception
     */
    public function addField(QUI\ERP\Products\Field\Field $Field): void
    {
        if ($this->fields === null) {
            $this->getFields();
        }

        /* @var $CategoryField QUI\ERP\Products\Field\Field */
        foreach ($this->fields as $CategoryField) {
            if ($CategoryField->getId() == $Field->getId()) {
                return;
            }
        }

        $this->fields[] = $Field;

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryAddField', [$this, $Field]);
    }

    /**
     * Return a category field
     *
     * @param integer $fieldId - Field-ID
     * @return ?Field
     */
    public function getField(int $fieldId): ?QUI\ERP\Products\Field\Field
    {
        $fields = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if ($Field->getId() == $fieldId) {
                return $Field;
            }
        }

        return null;
    }

    /**
     * Clear the fields in the category
     *
     * @throws QUI\Exception
     */
    public function clearFields(): void
    {
        $this->fields = [];

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryClearFields', [$this]);
    }

    /**
     * saves the field
     *
     * @param User|null $User
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function save(null | User $User = null): void
    {
        QUI\Permissions\Permission::checkPermission('category.edit', $User);

        $fields = [];

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->getFields() as $Field) {
            if ($Field->isStandard() || $Field->isSystem()) {
                continue;
            }

            $attributes['id'] = $Field->getId();
            $attributes['publicStatus'] = $Field->getAttribute('publicStatus') ? 1 : 0;
            $attributes['searchStatus'] = $Field->getAttribute('searchStatus') ? 1 : 0;

            $fields[] = $attributes;
        }

        if (class_exists('\QUI\Watcher')) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/products', 'watcher.message.category.save', [
                    'id' => $this->getId()
                ]),
                'Category->save',
                $fields
            );
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            [
                'fields' => json_encode($fields),
                'parentId' => $this->getParentId(),
                'custom_data' => json_encode($this->getCustomData())
            ],
            ['id' => $this->getId()]
        );

        Categories::clearCache($this->getId());

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategorySave', [$this]);
    }

    /**
     * delete the complete category
     *
     * @param User|null $User
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function delete(null | User $User = null): void
    {
        if ($this->getId() === 0) {
            return;
        }

        QUI\Permissions\Permission::checkPermission('category.delete', $User);

        if (class_exists('\QUI\Watcher')) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/products', 'watcher.message.category.delete', [
                    'id' => $this->getId(),
                    'title' => $this->getTitle()
                ])
            );
        }

        // get children ids
        $ids = [];

        $recursiveHelper = function ($parentId) use (&$ids, &$recursiveHelper) {
            try {
                $Category = Categories::getCategory($parentId);
                $children = $Category->getChildren();

                $ids[] = $Category->getId();

                /* @var $Child QUI\ERP\Products\Category\Category */
                foreach ($children as $Child) {
                    $recursiveHelper($Child->getId());
                }
            } catch (QUI\Exception) {
            }
        };

        $recursiveHelper($this->getId());

        foreach ($ids as $id) {
            $id = (int)$id;

            if (!$id) {
                continue;
            }

            QUI::getDataBase()->delete(
                QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                ['id' => $id]
            );

            QUI\Translator::delete(
                'quiqqer/products',
                'products.category.' . $id . '.title'
            );

            QUI\Translator::delete(
                'quiqqer/products',
                'products.category.' . $id . '.description'
            );

            Categories::clearCache($id);
        }

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryDelete', [$this]);
    }

    /**
     * Get all fields that are set as searchable for this category
     *
     * @return array
     */
    public function getSearchFields(): array
    {
        $searchFields = [];
        $fields = $this->getFields();

        foreach ($fields as $Field) {
            /* @var $Field QUI\ERP\Products\Field\Field */
            if ($Field->getAttribute('searchStatus')) {
                $searchFields[] = $Field;
            }
        }

        return $searchFields;
    }

    //region caching

    /**
     * @return string
     */
    protected function getSiteCacheName(): string
    {
        return QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'category/' . $this->getId() . '/sites';
    }

    //endregion

    // region Custom data

    /**
     * @param string $key
     * @param float|array|int|string|null $value - Must be serializable
     */
    public function setCustomDataEntry(string $key, float | array | int | string | null $value): void
    {
        if (!is_string($value) && !is_numeric($value) && !is_array($value)) {
            return;
        }

        $this->customData[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null - Custom entry value or NULL if it does not exist
     */
    public function getCustomDataEntry(string $key): mixed
    {
        if (array_key_exists($key, $this->customData)) {
            return $this->customData[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getCustomData(): array
    {
        return $this->customData;
    }

    // endregion
}
