<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\Category
 */
namespace QUI\ERP\Products\Interfaces;

use QUI\Locale;
use QUI\Projects\Project;

/**
 * Interface Category
 * @package QUI\ERP\Products
 */
interface Category
{
    /**
     * Return the Category-ID
     *
     * @return integer
     */
    public function getId();

    /**
     * Return the translated title
     *
     * @param null|Locale $Locale
     * @return string
     */
    public function getTitle($Locale = null);

    /**
     * Return the translated description
     *
     * @param null|Locale $Locale
     * @return string
     */
    public function getDescription($Locale = null);

    /**
     * Return the to the category
     *
     * @param null|Project $Project
     * @return string
     */
    public function getUrl($Project = null);

    /**
     * Return the Id of the parent category
     * Category 0 has no parent => returns false
     *
     * @return integer|boolean
     */
    public function getParentId();

    /**
     * Return the the parent category
     * Category 0 has no parent => returns false
     *
     * @return bool|Category
     * @throws \QUI\Exception
     */
    public function getParent();

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Return the sub categories
     *
     * @return array
     */
    public function getChildren();

    /**
     * Count the subcategories
     *
     * @return integer
     */
    public function countChildren();

    /**
     * Return the category site
     *
     * @param \QUI\Projects\Project|null $Project
     * @return \QUI\Projects\Site
     *
     * @throws \QUI\Exception
     */
    public function getSite($Project = null);

    /**
     * Return all sites which assigned the category
     *
     * @param \QUI\Projects\Project|null $Project
     * @return array
     */
    public function getSites($Project = null);

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
    public function getProducts($params = array());

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
    public function getProductIds($params = array());

    /**
     * Return the number of the products in the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['debug']
     * @return integer
     */
    public function countProducts($params = array());

    /**
     * Return the category fields
     *
     * @return array
     */
    public function getFields();

    /**
     * Return a category field
     *
     * @param integer $fieldId - Field-ID
     * @return \QUI\ERP\Products\Field\Field
     */
    public function getField($fieldId);

    /**
     * Get all fields that are set as searchable for this category
     *
     * @return array
     */
    public function getSearchFields();
}
