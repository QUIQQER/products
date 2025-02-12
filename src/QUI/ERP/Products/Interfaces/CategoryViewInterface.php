<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\CategoryViewInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Field\Field;
use QUI\Exception;
use QUI\Locale;
use QUI\Projects\Project;
use QUI\Interfaces\Projects\Site;

/**
 * Interface Category for the view
 * has only getter methods
 *
 * @package QUI\ERP\Products
 */
interface CategoryViewInterface
{
    /**
     * Return the Category-ID
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Return the translated title
     *
     * @param null|Locale $Locale
     * @return string
     */
    public function getTitle(null | Locale $Locale = null): string;

    /**
     * Return the translated description
     *
     * @param null|Locale $Locale
     * @return string
     */
    public function getDescription(null | Locale $Locale = null): string;

    /**
     * Return the category
     *
     * @param Project|null $Project
     * @return string
     */
    public function getUrl(null | Project $Project = null): string;

    /**
     * Return the ID of the parent category
     * - false has no parent => returns false
     *
     * @return integer|boolean
     * @throws Exception
     */
    public function getParentId(): bool|int;

    /**
     * Return the parent category
     * - false has no parent => returns false
     *
     * @return bool|CategoryInterface
     * @throws Exception
     */
    public function getParent(): bool|CategoryInterface;

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes(): array;

    /**
     * Return the sub categories
     *
     * @return array
     */
    public function getChildren(): array;

    /**
     * Count the subcategories
     *
     * @return integer
     */
    public function countChildren(): int;

    /**
     * Return the category site
     *
     * @param Project|null $Project
     * @return Site
     *
     * @throws Exception
     */
    public function getSite(null | Project $Project = null): Site;

    /**
     * Return all sites which assigned the category
     *
     * @param Project|null $Project
     * @return array
     */
    public function getSites(null | Project $Project = null): array;

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
    public function getProducts(array $params = []): array;

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
    public function getProductIds(array $params = []): array;

    /**
     * Return the number of the products in the category
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['debug']
     * @return integer
     */
    public function countProducts(array $params = []): int;

    /**
     * Return the category fields
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Return a category field
     *
     * @param integer $fieldId - Field-ID
     * @return Field|null
     */
    public function getField(int $fieldId): ?Field;

    /**
     * Get all fields that are set as searchable for this category
     *
     * @return array
     */
    public function getSearchFields(): array;
}
