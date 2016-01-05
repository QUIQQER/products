<?php

/**
 * This file contains QUI\ERP\Products\Field\Controller
 */
namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class Controller
 * - Field data connection
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID );
 */
class Controller
{
    /**
     * @var Modell
     */
    protected $Field;

    /**
     * Controller constructor.
     * @param Modell $Field
     */
    public function __construct(Modell $Field)
    {
        $this->Field = $Field;
    }

    /**
     * Return the Product Modell
     * @return Modell
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
        QUI\Rights\Permission::checkPermission('field.edit');

        QUI::getDataBase()->update(
            QUI\ERP\Products\Tables::getFieldTable(),
            array('name' => $this->Field->getAttribute('name')),
            array('id' => $this->Field->getId())
        );
    }

    /**
     * Delete the complete field
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('field.delete');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Tables::getFieldTable(),
            array('id' => $this->Field->getId())
        );
    }
}
