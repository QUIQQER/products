<?php

/**
 * This file contains QUI\ERP\Products\Category\Model
 */
namespace QUI\ERP\Products\Category;

use QUI;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class Category
 * Category Model
 *
 * @package QUI\ERP\Products\Category
 *
 * @example
 * QUI\ERP\Products\Handler\Categories::getCategory( ID );
 */
class Category extends QUI\QDOM
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
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false)
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
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getDescription($Locale = false)
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
     * @return array
     */
    public function getAttributes()
    {
        $sites  = array();
        $fields = array();

        $siteList = $this->getSites();
        $fieldist = $this->getFields();

        /* @var $Site QUI\Projects\Site */
        foreach ($siteList as $projectName => $projectData) {
            foreach ($projectData as $projectLang => $Site) {
                $sites[$projectName][$projectLang] = $Site->getId();
            }
        }

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fieldist as $Field) {
            $fields[] = $Field->getAttributes();
        }

        $attributes       = parent::getAttributes();
        $attributes['id'] = $this->getId();

        $attributes['title']         = $this->getTitle();
        $attributes['description']   = $this->getDescription();
        $attributes['countChildren'] = $this->countChildren();
        $attributes['sites']         = $sites;
        $attributes['fields']        = $fields;

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
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            'count' => array(
                'select' => 'id',
                'as' => 'id'
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
     * @param QUI\Projects\Project $Project
     * @return QUI\Projects\Site
     *
     * @throws QUI\Exception
     */
    public function getSite(QUI\Projects\Project $Project)
    {
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

        throw new QUI\Exception(array(
            'quiqqer/products',
            'exception.category.has.no.site'
        ));
    }

    /**
     * Return all sites which assigned the category
     *
     * @param QUI\Projects\Project|boolean $Project
     * @return array
     */
    public function getSites($Project = false)
    {
        if (is_null($this->sites)) {
            $this->sites = array();

            $data = $this->data;

            if (isset($data['sites'])) {
                $sites = json_decode($data['sites'], true);

                if (!is_array($sites)) {
                    $sites = array();
                }

                foreach ($sites as $siteData) {
                    try {
                        $Project = QUI::getProject($siteData['project'], $siteData['lang']);
                        $Site    = $Project->get($siteData['id']);

                        if ($siteData['standard'] == 1) {
                            $this->defaultSites[$Project->getName()][$Project->getLang()] = $Site;
                        }

                        $this->sites[] = $Site;

                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeException(
                            $Exception,
                            QUI\System\Log::LEVEL_DEBUG
                        );
                    }
                }
            }
        }

        if ($Project === false) {
            return $this->sites;
        }

        if (get_class($Project) !== 'QUI\Projects\Project') {
            return array();
        }

        $sites  = array();
        $id     = $this->getId();
        $result = $this->sites;

        $projectName = $Project->getName();
        $projectLang = $Project->getLang();

        /* @var $Site QUI\Projects\Site */
        foreach ($result as $Site) {
            if ($Site->getProject()->getName() != $projectName) {
                continue;
            }

            if ($Site->getProject()->getLang() != $projectLang) {
                continue;
            }

            if ($Site->getAttribute('quiqqer.products.settings.categoryId') == $id) {
                $sites[] = $Site;
            }
        }

        return $sites;
    }

    /**
     * Products
     */

    /**
     * Return all products from the category
     *
     * @return array
     */
    public function getProducts()
    {
        return QUI\ERP\Products\Handler\Products::getProducts(array(
            'where' => array(
                'categories' => array(
                    'type' => '%LIKE%',
                    'value' => ',' . $this->getId() . ','
                )
            ),
            'limit' => 20
        ));
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
            $this->fields = array();

            $data = $this->data;

            if (isset($data['fields'])) {
                $fields = json_decode($data['fields'], true);

                if (!is_array($fields)) {
                    $fields = array();
                }

                foreach ($fields as $field) {
                    try {
                        $Field = Fields::getField($field['id']);
                        $Field->setAttribute('publicStatus', $field['publicStatus']);
                        $Field->setAttribute('searchStatus', $field['searchStatus']);

                        $this->fields[] = $Field;

                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeException(
                            $Exception,
                            QUI\System\Log::LEVEL_DEBUG
                        );
                    }
                }
            }
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
    }

    /**
     * saves the field
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('category.edit');

        $fields = array();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->getFields() as $Field) {
            $attributes                 = $Field->getAttributes();
            $attributes['publicStatus'] = $Field->getAttribute('publicStatus') ? 1 : 0;
            $attributes['searchStatus'] = $Field->getAttribute('searchStatus') ? 1 : 0;

            $fields[] = $attributes;
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array(
                'fields' => json_encode($fields)
            ),
            array('id' => $this->getId())
        );

        QUI\ERP\Products\Handler\Categories::clearCache($this->getId());
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        if ($this->getId() === 0) {
            return;
        }

        if ($this->getId() === 0) {
            return;
        }

        QUI\Rights\Permission::checkPermission('category.delete');

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
    }
}
