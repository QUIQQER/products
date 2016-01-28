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
    protected $Modell;

    /**
     * Controller constructor.
     * @param Category $Modell
     */
    public function __construct(Category $Modell)
    {
        $this->Modell = $Modell;
    }

    /**
     * Return the Product Modell
     * @return Category
     */
    public function getModell()
    {
        return $this->Modell;
    }

    /**
     * Save the data to the database
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('category.edit');

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array('name' => $this->getModell()->getAttribute('name')),
            array('id' => $this->getModell()->getId())
        );
    }

    /**
     * Delete the complete field
     * ID 0 cant be deleted
     */
    public function delete()
    {
        if ($this->getModell()->getId() === 0) {
            return;
        }

        QUI\Rights\Permission::checkPermission('category.delete');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array('id' => $this->Modell->getId())
        );

        QUI\Translator::delete(
            'quiqqer/products',
            'products.category.' . $this->getModell()->getId() . '.title'
        );
    }
}
