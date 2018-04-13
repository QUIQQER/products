<?php

/**
 * This file contains QUI\ERP\Products\Category\ViewFrontend
 */

namespace QUI\ERP\Products\Category;

use QUI;

/**
 * Class ViewFrontend
 *
 * @package QUI\ERP\Products\Category
 */
class ViewFrontend implements QUI\ERP\Products\Interfaces\CategoryViewInterface
{
    /**
     * Real category
     *
     * @var null
     */
    protected $Category = null;

    /**
     * View constructor
     *
     * @param Category $Category
     */
    public function __construct(Category $Category)
    {
        $this->Category = $Category;
    }

    /**
     * Count the subcategories
     *
     * @return int
     */
    public function countChildren()
    {
        return $this->Category->countChildren();
    }

    /**
     * Return the sub categories
     *
     * @param array $params
     * @return integer
     */
    public function countProducts($params = [])
    {
        $params['where']['active'] = 1;

        return $this->Category->countProducts($params);
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->Category->getAttributes();
    }

    /**
     * Return the sub categories
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->Category->getChildren();
    }

    /**
     * Return the translated description
     *
     * @param null $Locale
     * @return string
     */
    public function getDescription($Locale = null)
    {
        return $this->Category->getDescription($Locale);
    }

    /**
     * @param int $fieldId
     * @return QUI\ERP\Products\Field\Field
     */
    public function getField($fieldId)
    {
        return $this->Category->getField($fieldId);
    }

    /**
     * Return the category fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->Category->getFields();
    }

    /**
     * Return the Category-ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->Category->getId();
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
        return $this->Category->getParent();
    }

    /**
     * Return the Id of the parent category
     * Category 0 has no parent => returns false
     *
     * @return bool|int
     */
    public function getParentId()
    {
        return $this->Category->getParentId();
    }

    /**
     * Return all active products from the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['limit']
     *                              $queryParams['order']
     *                              $queryParams['debug']
     *
     * @return array
     * @param array $params
     * @return array
     */
    public function getProducts($params = [])
    {
        $params['where']['active'] = 1;

        return $this->Category->getProducts($params);
    }

    /**
     * Return the number of active products in the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['debug']
     * @param array $params
     * @return array
     */
    public function getProductIds($params = [])
    {
        $params['where']['active'] = 1;

        return $this->Category->getProductIds($params);
    }

    /**
     * Get all fields that are set as searchable for this category
     *
     * @return array
     */
    public function getSearchFields()
    {
        return $this->Category->getSearchFields();
    }

    /**
     * Return the category site
     *
     * @param \QUI\Projects\Project|null $Project
     * @return \QUI\Projects\Site
     *
     * @throws \QUI\Exception
     */
    public function getSite($Project = null)
    {
        return $this->Category->getSite($Project);
    }

    /**
     * Return all sites which assigned the category
     *
     * @param \QUI\Projects\Project|null $Project
     * @return array
     */
    public function getSites($Project = null)
    {
        return $this->Category->getSites($Project);
    }

    /**
     * Return the translated title
     *
     * @param null|QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = null)
    {
        return $this->Category->getTitle($Locale);
    }

    /**
     * Return the to the category
     *
     * @param null|QUI\Projects\Project $Project
     * @return string
     */
    public function getUrl($Project = null)
    {
        return $this->getUrl($Project);
    }
}
