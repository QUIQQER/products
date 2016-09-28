<?php

/**
 * This file contains QUI\ERP\Products\Category\Model
 */
namespace QUI\ERP\Products\Category;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Product;

/**
 * Class Category
 * Category Model
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
    protected $id;

    /**
     * Parent-ID
     *
     * @var integer
     */
    protected $parentId;

    /**
     * @var array
     */
    protected $fields = null;

    /**
     * @var array
     */
    protected $sites = null;

    /**
     * @var null|QUI\Projects\Site
     */
    protected $defaultSites = array();

    /**
     * db data
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $caches = array();

    /**
     * Model constructor.
     *
     * @param integer $categoryId
     * @param array $data - optional, category data
     */
    public function __construct($categoryId, $data)
    {
        $this->parentId = 0;
        $this->id       = (int)$categoryId;
        $this->data     = $data;

        $this->caches = array(
            'site-binds'
        );

        if (isset($data['parentId'])) {
            $this->parentId = (int)$data['parentId'];
        }

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }
    }

    /**
     * Return the title / name of the category
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getTitle($Locale = null)
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
    public function getDescription($Locale = null)
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return category url
     * Return the category url of a binded site from the project
     *
     * @param QUI\Projects\Project|null $Project - optional, default = global project
     * @return string
     */
    public function getUrl($Project = null)
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
     * Return the Id of the parent category
     * Category 0 has no parent => returns false
     *
     * @return integer|boolean
     */
    public function getParentId()
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
    public function setParentId($parentId)
    {
        $parentId = (int)$parentId;

        if ($parentId == $this->getId()) {
            return;
        }

        // exists the category?
        if ($parentId !== 0) {
            QUI\ERP\Products\Handler\Categories::getCategory($parentId);
        }

        $this->parentId = $parentId;
    }

    /**
     * Return the the parent category
     * Category 0 has no parent => returns false
     *
     * @return bool|Category
     * @throws QUI\Exception
     */
    public function getParent()
    {
        if ($this->getId() === 0) {
            return false;
        }

        return QUI\ERP\Products\Handler\Categories::getCategory($this->parentId);
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $cacheName = QUI\ERP\Products\Handler\Categories::getCacheName($this->getId()) . '/attributes';

        $fields   = array();
        $fieldist = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fieldist as $Field) {
            $fields[] = $Field->getAttributes();
        }

        try {
            $attributes = QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Cache\Exception $Exception) {
            $attributes       = parent::getAttributes();
            $attributes['id'] = $this->getId();

            $attributes['countChildren'] = $this->countChildren();
            $attributes['sites']         = $this->getSites();
            $attributes['parent']        = $this->getParentId();

            QUI\Cache\Manager::set($cacheName, $attributes);
        }

        $attributes['title']       = $this->getTitle();
        $attributes['description'] = $this->getDescription();
        $attributes['fields']      = $fields;

        return $attributes;
    }

    /**
     * @return ViewFrontend|ViewBackend
     */
    public function getView()
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
    public function getViewFrontend()
    {
        return new ViewFrontend($this);
    }

    /**
     * @return ViewBackend
     */
    public function getViewBackend()
    {
        return new ViewBackend($this);
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return QUI\ERP\Products\Handler\Categories::getCategories(array(
            'where' => array(
                'parentId' => $this->getId()
            )
        ));
    }

    /**
     * Return the number of the children
     *
     * @return integer
     */
    public function countChildren()
    {
        $data = QUI::getDataBase()->fetch(array(
            'from'  => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            'count' => array(
                'select' => 'id',
                'as'     => 'id'
            ),
            'where' => array(
                'parentId' => $this->getId()
            )
        ));

        if (isset($data[0]) && isset($data[0]['id'])) {
            return (int)$data[0]['id'];
        }

        return 0;
    }

    /**
     * Return the category site
     *
     * @param QUI\Projects\Project|null $Project
     * @return QUI\Projects\Site
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Category\Exception
     */
    public function getSite($Project = null)
    {
        if (!$Project) {
            $Project = QUI::getRewrite()->getProject();
        }

        $defaults = $this->defaultSites;
        $name     = $Project->getName();
        $lang     = $Project->getLang();

        if (isset($defaults[$name]) && isset($defaults[$name][$lang])) {
            return $defaults[$name][$lang];
        }

        $sites = $this->getSites($Project);

        if (isset($sites[0])) {
            return $sites[0];
        }

        QUI\System\Log::addWarning(
            QUI::getLocale()->get('quiqqer/products', 'exception.category.has.no.site', array(
                'id'    => $this->getId(),
                'title' => $this->getTitle()
            ))
        );

        return $Project->firstChild();
    }

    /**
     * Return all sites which assigned the category
     *
     * @param QUI\Projects\Project|null $Project
     * @return array
     */
    public function getSites($Project = null)
    {
        if (!is_null($this->sites) && !$Project) {
            return $this->sites;
        }

        if (isset($this->data['sites'])) {
            // @todo load from data
        }

        if (is_null($this->sites)) {
            $this->refreshSiteBinds();
        }

        if (!$Project) {
            return $this->sites;
        }

        $sites  = array();
        $id     = $this->getId();
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

            if ($Site->getAttribute('quiqqer.products.settings.categoryId') == $id ||
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
     * @return array
     */
    public function refreshSiteBinds()
    {
        // must be cached or set it at the site save event
        $projects = QUI::getProjectManager()->getProjectList();
        $result   = array();
        $id       = $this->getId();

        foreach ($projects as $Project) {
            /* @var $Project QUI\Projects\Project */
            $sites = $Project->getSites(array(
                'where' => array(
                    'type' => 'quiqqer/products:types/category'
                )
            ));

            $idList = array();
            $debug  = array();

            foreach ($sites as $Site) {
                /* @var $Site QUI\Projects\Site */
                $siteCatId = $Site->getAttribute('quiqqer.products.settings.categoryId');

                if ($siteCatId != ''
                    && $siteCatId !== false
                    && ($siteCatId == $id || $siteCatId == 0)
                ) {
                    $result[] = $Site;
                    $idList[] = $Site->getId();
                    $debug[]  = $siteCatId;
                }
            }

            $this->data['sites'][$Project->getName()][$Project->getLang()] = $idList;
        }

        $this->sites = $result;

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
    public function getProducts($params = array())
    {
        $query = array(
            'limit' => 20
        );

        $where = array(
            'categories' => array(
                'type'  => '%LIKE%',
                'value' => ',' . $this->getId() . ','
            )
        );

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
    public function getProductIds($params = array())
    {
        $query = array();

        $where = array(
            'categories' => array(
                'type'  => '%LIKE%',
                'value' => ',' . $this->getId() . ','
            )
        );

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
    public function countProducts($params = array())
    {
        if (!is_array($params)) {
            $params = array();
        }

        $query = array();

        $where = array(
            'categories' => array(
                'type'  => '%LIKE%',
                'value' => ',' . $this->getId() . ','
            )
        );

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
     *
     * @todo auslagern auf queue, wenn queue service existiert
     * @todo ansonsten über junks aufbauen
     * @todo vorsicht wegen timeouts bei 10.000 produkten
     */
    public function setFieldsToAllProducts()
    {
        $productIds     = $this->getProductIds();
        $fields         = $this->getFields();
        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($productIds as $productId) {
            set_time_limit(3);

            try {
                $Product = new Product($productId);

                foreach ($fields as $Field) {
                    $Product->addField($Field);
                    $Product->save();
                }
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        QUI::getEvents()->fireEvent(
            'onQuiqqerProductsCategorySetFieldsToAllProducts',
            array($this)
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
    public function getFields()
    {
        if (is_null($this->fields)) {
            $fields = array();
            $data   = $this->data;

            $standardFields = Fields::getStandardFields();

            $isFieldInArray = function ($Field, $array = array()) {
                /* @var QUI\ERP\Products\Field\Field $Field */
                /* @var QUI\ERP\Products\Field\Field $Entry */
                foreach ($array as $Entry) {
                    if ($Entry->getId() == $Field->getId()) {
                        return true;
                    }
                }
                return false;
            };

            if (isset($data['fields'])) {
                $jsonData = json_decode($data['fields'], true);

                if (!is_array($jsonData)) {
                    $jsonData = array();
                }

                foreach ($jsonData as $field) {
                    try {
                        $Field = Fields::getField($field['id']);
                        $Field->setAttribute('publicStatus', $field['publicStatus']);
                        $Field->setAttribute('searchStatus', $field['searchStatus']);

                        if (isset($field['options']) && !empty($field['options'])) {
                            $Field->setOptions($field['options']);
                        }

                        $fields[] = $Field;
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
                if (!$isFieldInArray($Field, $fields)) {
                    $fields[] = $Field;
                }
            }

            $this->fields = $fields;
        }

        return $this->fields;
    }

    /**
     * Add a field to the category
     *
     * @param QUI\ERP\Products\Field\Field $Field
     */
    public function addField(QUI\ERP\Products\Field\Field $Field)
    {
        if (is_null($this->fields)) {
            $this->getFields();
        }

        /* @var $CategoryField QUI\ERP\Products\Field\Field */
        foreach ($this->fields as $CategoryField) {
            if ($CategoryField->getId() == $Field->getId()) {
                return;
            }
        }

        $this->fields[] = $Field;

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryAddField', array($this, $Field));
    }

    /**
     * Return a category field
     *
     * @param integer $fieldId - Field-ID
     * @return QUI\ERP\Products\Field\Field|bool
     */
    public function getField($fieldId)
    {
        $fields = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if ($Field->getId() == $fieldId) {
                return $Field;
            }
        }

        return false;
    }

    /**
     * Clear the fields in the category
     */
    public function clearFields()
    {
        $this->fields = array();

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryClearFields', array($this));
    }

    /**
     * saves the field
     *
     * @param boolean|QUI\Interfaces\Users\User $User
     */
    public function save($User = false)
    {
        QUI\Permissions\Permission::checkPermission('category.edit', $User);

        $fields = array();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->getFields() as $Field) {
            if ($Field->isStandard() || $Field->isSystem()) {
                continue;
            }

            $attributes['id']           = $Field->getId();
            $attributes['publicStatus'] = $Field->getAttribute('publicStatus') ? 1 : 0;
            $attributes['searchStatus'] = $Field->getAttribute('searchStatus') ? 1 : 0;

            $fields[] = $attributes;
        }

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.category.save', array(
                'id' => $this->getId()
            )),
            'Category->save',
            $fields
        );

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array(
                'fields'   => json_encode($fields),
                'parentId' => $this->getParentId()
            ),
            array('id' => $this->getId())
        );

        QUI\ERP\Products\Handler\Categories::clearCache($this->getId());

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategorySave', array($this));
    }

    /**
     * delete the complete category
     *
     * @param boolean|QUI\Interfaces\Users\User $User
     */
    public function delete($User = false)
    {
        if ($this->getId() === 0) {
            return;
        }

        if ($this->getId() === 0) {
            return;
        }

        QUI\Permissions\Permission::checkPermission('category.delete', $User);

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.category.delete', array(
                'id'    => $this->getId(),
                'title' => $this->getTitle()
            ))
        );

        // get children ids
        $ids = array();

        $recursiveHelper = function ($parentId) use (&$ids, &$recursiveHelper) {
            try {
                $Category = QUI\ERP\Products\Handler\Categories::getCategory($parentId);
                $children = $Category->getChildren();

                $ids[] = $Category->getId();

                /* @var $Child QUI\ERP\Products\Category\Category */
                foreach ($children as $Child) {
                    $recursiveHelper($Child->getId(), $ids, $recursiveHelper);
                }
            } catch (QUI\Exception $Exception) {
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
                array('id' => $id)
            );

            QUI\Translator::delete(
                'quiqqer/products',
                'products.category.' . $id . '.title'
            );

            QUI\ERP\Products\Handler\Categories::clearCache($id);
        }

        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryDelete', array($this));
    }

    /**
     * Get all fields that are set as searchable for this category
     *
     * @return array
     */
    public function getSearchFields()
    {
        $searchFields = array();
        $fields       = $this->getFields();

        foreach ($fields as $Field) {
            /* @var $Field QUI\ERP\Products\Field\Field */
            if ($Field->getAttribute('searchStatus')) {
                $searchFields[] = $Field;
            }
        }

        return $searchFields;
    }
}
