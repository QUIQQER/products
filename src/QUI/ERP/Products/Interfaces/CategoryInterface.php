<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\CategoryInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI;
use QUI\ERP\Products\Field\Field;
use QUI\Exception;
use QUI\Interfaces\Users\User;

/**
 * Interface Category
 * @package QUI\ERP\Products
 */
interface CategoryInterface extends QUI\QDOMInterface, CategoryViewInterface
{
    /**
     * Set a new parent to the category
     *
     * @param integer $parentId
     * @throws Exception
     */
    public function setParentId(int $parentId): void;

    /**
     * Add a field to the category
     *
     * @param Field $Field
     */
    public function addField(Field $Field): void;

    /**
     * Set all field settings to all products in the category
     *
     * @return void
     */
    public function setFieldsToAllProducts(): void;

    /**
     * Clear the fields in the category
     */
    public function clearFields(): void;

    /**
     * Delete the category
     *
     * @param User|null $User $User
     */
    public function delete(null | User $User = null): void;

    /**
     * Save the category
     *
     * @param User|null $User $User
     */
    public function save(null | User $User = null): void;
}
