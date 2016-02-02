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
     * @var Field
     */
    protected $Field;

    /**
     * Controller constructor.
     * @param Field $Field
     */
    public function __construct(Field $Field)
    {
        $this->Field = $Field;
    }

    /**
     * Return the Product Modell
     * @return Field
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

        $allowedAttributes = QUI\ERP\Products\Handler\Fields::getChildAttributes();

        $data = array();

        foreach ($allowedAttributes as $attribute) {
            if ($this->Field->getAttribute($attribute)) {
                $data[$attribute] = $this->Field->getAttribute($attribute);
            } else {
                $data[$attribute] = '';
            }
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data,
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
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            array('id' => $this->Field->getId())
        );

        // delete the locale
        QUI\Translator::delete(
            'quiqqer/products',
            'products.field.' . $this->Field->getId() . '.title'
        );
    }
}
