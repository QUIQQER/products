<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Interfaces\FieldInterface as Field;
use QUI\ERP\Products\Interfaces\CategoryInterface;

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
    public function addField(Field $Field): void
    {
        if (!isset($this->fields[$Field->getId()])) {
            $this->fields[$Field->getId()] = clone $Field;

            if ($Field instanceof QUI\ERP\Products\Field\Types\AttributeGroup) {
                $vDefaultId = QUI\ERP\Products\Handler\Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES;

                if (isset($this->fields[$vDefaultId])) {
                    unset($this->fields[$vDefaultId]);
                }
            }

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
     * Add an own product field
     * This field is explicit added to the product
     *
     * @param QUI\ERP\Products\Field\Field $Field
     *
     * @throws QUI\Exception
     */
    public function addOwnField(QUI\ERP\Products\Field\Field $Field): void
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
    public function removeField(Field $Field): void
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
     * @param CategoryInterface $Category
     */
    public function addCategory(CategoryInterface $Category): void
    {
        $this->categories[$Category->getId()] = $Category;
    }

    /**
     * Set the main category
     *
     * @param integer|string|CategoryInterface $Category
     * @throws QUI\Exception
     */
    public function setMainCategory(CategoryInterface | int | string $Category): void
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
    public function setPriority(int $priority): void
    {
        $this->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRIORITY)->setValue($priority);
    }

    /**
     * Set own product permissions
     *
     * @param string $permission
     * @param string $ugString - user group string
     * @param QUI\Interfaces\Users\User|null $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function setPermission(
        string $permission,
        string $ugString = '',
        null | QUI\Interfaces\Users\User $User = null
    ): void {
        if (!QUI\Utils\UserGroups::isUserGroupString($ugString)) {
            return;
        }

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
     * @param QUI\Interfaces\Users\User|null $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function setPermissions(array $permissions, null | QUI\Interfaces\Users\User $User = null): void
    {
        foreach ($permissions as $permission => $data) {
            $this->setPermission($permission, $data, $User);
        }
    }

    //region calc

    /**
     * @param null $Calc
     * @return $this
     */
    public function calc($Calc = null): static
    {
        return $this;
    }

    /**
     * @return void
     */
    public function resetCalculation()
    {
        // nothing
    }

    //endregion
}
