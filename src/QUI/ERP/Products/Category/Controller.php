<?php

/**
 * This file contains QUI\ERP\Products\Category\Controller
 */
namespace QUI\ERP\Products\Category;

use QUI;

/**
 * Class Controller
 * - Category data connection
 *
 * @package QUI\ERP\Products\Category
 *
 * @example
 * QUI\ERP\Products\Handler\Categories::getCategory( ID );
 */
class Controller
{
    /**
     * @var Category
     */
    protected $Field;

    /**
     * Controller constructor.
     * @param Category $Field
     */
    public function __construct(Category $Field)
    {
        $this->Field = $Field;
    }

    /**
     * Return the Product Modell
     * @return Category
     */
    public function getModell()
    {
        return $this->Field;
    }

    /**
     * Save the data to the database
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('category.edit');

        QUI::getDataBase()->update(
            QUI\ERP\Products\Tables::getCategoryTableName(),
            array('name' => $this->Field->getAttribute('name')),
            array('id' => $this->Field->getId())
        );
    }

    /**
     * Delete the complete field
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('category.delete');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Tables::getCategoryTableName(),
            array('id' => $this->Field->getId())
        );
    }
}
