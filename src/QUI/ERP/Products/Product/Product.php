<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\FieldInterface as Field;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;

/**
 * Class Product
 * - Controller
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Product extends Model implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * Add a field to the product
     *
     * @param Field $Field
     *
     * @throws QUI\Exception
     */
    public function addField(Field $Field)
    {
        if (!isset($this->fields[$Field->getId()])) {
            $this->fields[$Field->getId()] = $Field;

            return;
        }

        /* @var QUI\ERP\Products\Field\Field $Exists */
        $Exists = $this->fields[$Field->getId()];

        $Exists->setUnassignedStatus($Field->isUnassigned());
        $Exists->setOwnFieldStatus($Field->isOwnField());
        $Exists->setPublicStatus($Field->isPublic());

        if ($Exists->isEmpty()) {
            $Exists->setValue($Exists->getDefaultValue());
        }
    }

    /**
     * Add a own product field
     * This field is explicit added to the product
     *
     * @param QUI\ERP\Products\Field\Field $Field
     *
     * @throws QUI\Exception
     */
    public function addOwnField(QUI\ERP\Products\Field\Field $Field)
    {
        $Field->setUnassignedStatus(false);
        $Field->setOwnFieldStatus(true);

        $this->addField($Field);
    }

    /**
     * Remove a field from the product
     *
     * @param Field $Field
     * @throws QUI\Exception
     */
    public function removeField(Field $Field)
    {
        if (!$Field->isOwnField()) {
            throw new QUI\Exception([
                'quiqqer/products',
                'exception.only.ownFields.deletable'
            ]);
        }

        if (isset($this->fields[$Field->getId()])) {
            unset($this->fields[$Field->getId()]);
        }
    }

    /**
     * Add the product to a category
     *
     * @param QUI\ERP\Products\Interfaces\CategoryInterface $Category
     */
    public function addCategory(QUI\ERP\Products\Interfaces\CategoryInterface $Category)
    {
        $this->categories[$Category->getId()] = $Category;
    }

    /**
     * Set the main category
     *
     * @param Category|integer $Category
     * @throws QUI\Exception
     */
    public function setMainCategory($Category)
    {
        if (!Categories::isCategory($Category)) {
            $Category = Categories::getCategory($Category);
        }

        $this->Category = $Category;
    }

    /**
     * Set the product priority
     *
     * @param integer $priority
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function setPriority($priority)
    {
        $this->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRIORITY)->setValue($priority);
    }

    /**
     * Set own product permissions
     *
     * @param string $permission
     * @param string $ugString - user group string
     * @param QUI\Interfaces\Users\User $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function setPermission($permission, $ugString = '', $User = null)
    {
        if (!QUI\Utils\UserGroups::isUserGroupString($ugString)) {
            return;
        };

        QUI\Permissions\Permission::checkPermission('product.setPermissions', $User);

        switch ($permission) {
            case 'permission.viewable':
            case 'permission.buyable':
                $this->permissions[$permission] = $ugString;
                break;
        }
    }

    /**
     * Set multiple permissions
     *
     * @param array $permissions - ist of permissions
     * @param QUI\Interfaces\Users\User $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function setPermissions($permissions, $User = null)
    {
        if (!\is_array($permissions)) {
            return;
        }

        foreach ($permissions as $permission => $data) {
            $this->setPermission($permission, $data, $User);
        }
    }

    //region calc

    /**
     * @param null $Calc
     * @return $this|mixed
     */
    public function calc($Calc = null)
    {
        return $this;
    }

    /**
     * @return mixed|void
     */
    public function resetCalculation()
    {
        // nothing
    }

    //endregion
}
