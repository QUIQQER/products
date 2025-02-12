<?php

/**
 * This file contains QUI\ERP\Products\Category\ViewFrontend
 */

namespace QUI\ERP\Products\Category;

use QUI;
use QUI\ERP\Products\Field\Field;
use QUI\Exception;
use QUI\Locale;
use QUI\Projects\Project;
use QUI\Interfaces\Projects\Site;

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
     * @var Category|null
     */
    protected ?Category $Category = null;

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
    public function countChildren(): int
    {
        return $this->Category->countChildren();
    }

    /**
     * Return the sub categories
     *
     * @param array $params
     * @return integer
     */
    public function countProducts(array $params = []): int
    {
        $params['where']['active'] = 1;

        return $this->Category->countProducts($params);
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->Category->getAttributes();
    }

    /**
     * Return the sub categories
     *
     * @return array
     */
    public function getChildren(): array
    {
        return $this->Category->getChildren();
    }

    /**
     * Return the translated description
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(null | Locale $Locale = null): string
    {
        return $this->Category->getDescription($Locale);
    }

    /**
     * @param int $fieldId
     * @return Field|null
     */
    public function getField(int $fieldId): ?Field
    {
        return $this->Category->getField($fieldId);
    }

    /**
     * Return the category fields
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->Category->getFields();
    }

    /**
     * Return the Category-ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->Category->getId();
    }

    /**
     * Return the parent category
     * - Category 0 has no parent => returns false
     *
     * @return bool|Category
     * @throws Exception
     */
    public function getParent(): bool | QUI\ERP\Products\Interfaces\CategoryInterface
    {
        return $this->Category->getParent();
    }

    /**
     * Return the ID of the parent category
     * - Category 0 has no parent => returns false
     *
     * @return bool|int
     */
    public function getParentId(): bool | int
    {
        return $this->Category->getParentId();
    }

    /**
     * Return all active products from the category
     *
     * @param array $params
     * @return array
     */
    public function getProducts(array $params = []): array
    {
        $params['where']['active'] = 1;

        return $this->Category->getProducts($params);
    }

    /**
     * Return the number of active products in the category
     *
     * @param array $params
     * @return array
     */
    public function getProductIds(array $params = []): array
    {
        $params['where']['active'] = 1;

        return $this->Category->getProductIds($params);
    }

    /**
     * Get all fields that are set as searchable for this category
     *
     * @return array
     */
    public function getSearchFields(): array
    {
        return $this->Category->getSearchFields();
    }

    /**
     * Return the category site
     *
     * @param Project|null $Project
     * @return Site
     *
     * @throws Exception
     */
    public function getSite(null | Project $Project = null): QUI\Interfaces\Projects\Site
    {
        return $this->Category->getSite($Project);
    }

    /**
     * Return all sites which assigned the category
     *
     * @param Project|null $Project
     * @return array
     * @throws Exception
     */
    public function getSites(null | Project $Project = null): array
    {
        return $this->Category->getSites($Project);
    }

    /**
     * Return the translated title
     *
     * @param null|Locale $Locale
     * @return string
     */
    public function getTitle(null | Locale $Locale = null): string
    {
        return $this->Category->getTitle($Locale);
    }

    /**
     * Return the URL of the category
     *
     * @param Project|null $Project
     * @return string
     * @throws Exception
     */
    public function getUrl(null | Project $Project = null): string
    {
        return $this->Category->getUrl($Project);
    }
}
