<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\CategoryInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI;

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
     * @throws \QUI\Exception
     */
    public function setParentId($parentId);

    /**
     * Add a field to the category
     *
     * @param \QUI\ERP\Products\Field\Field $Field
     */
    public function addField(QUI\ERP\Products\Field\Field $Field);

    /**
     * Set all field settings to all products in the category
     *
     * @return mixed
     */
    public function setFieldsToAllProducts();

    /**
     * Clear the fields in the category
     */
    public function clearFields();

    /**
     * Delete the category
     *
     * @param bool|\QUI\Interfaces\Users\User $User
     */
    public function delete($User = false);

    /**
     * Save the category
     *
     * @param bool|\QUI\Interfaces\Users\User $User
     */
    public function save($User = false);
}
