<?php

/**
 * This file contains QUI\ERP\Products\Category\Modell
 */
namespace QUI\ERP\Products\Category;

use QUI;

/**
 * Class Category
 * Category Modell
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
     * @var
     */
    protected $fields;

    /**
     * Modell constructor.
     *
     * @param integer $categoryId
     * @param array $data - optional, category data
     */
    public function __construct($categoryId, $data)
    {
        $this->parentId = 0;
        $this->id       = (int)$categoryId;

        if (isset($data['fields'])) {

        }

        if (isset($data['parentId'])) {
            $this->parentId = (int)$data['parentId'];
        }

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }
    }

    /**
     * @return Controller
     */
    protected function getController()
    {
        return new Controller($this);
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
        $attributes       = parent::getAttributes();
        $attributes['id'] = $this->getId();

        $attributes['title']         = $this->getTitle();
        $attributes['description']   = $this->getDescription();
        $attributes['countChildren'] = $this->countChildren();

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

    }

    /**
     * saves the field
     */
    public function save()
    {
        $this->getController()->save();
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        if ($this->getId() === 0) {
            return;
        }

        $this->getController()->delete();
    }
}
