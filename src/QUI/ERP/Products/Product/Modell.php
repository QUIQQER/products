<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class Controller
 * Product Model
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Modell extends QUI\QDOM
{
    /**
     * Product-ID
     * @var
     */
    protected $id;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * Model constructor
     *
     * @param integer $pid
     *
     * @throws QUI\Exception
     */
    public function __construct($pid)
    {
        $this->id = (int)$pid;

        $result = QUI::getDataBase()->fetch(array(
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
            'where' => array(
                'id' => $this->getId()
            )
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.product.not.found'),
                404,
                array('id' => $this->getId())
            );
        }

        unset($result[0]['id']);

        $this->setAttributes($result[0]);

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }
    }

    /**
     * @return ViewFrontend
     */
    public function getView()
    {
        switch ($this->getAttribute('viewType')) {
            case 'backend':
                return $this->getViewBackend();

            default:
                return $this->getViewFrontend();
        }
    }

    /**
     * @return ViewFrontend
     */
    public function getViewFrontend()
    {
        return new ViewFrontend($this);
    }

    /**
     * @return ViewBackend
     */
    public function getViewBackend()
    {
        return new ViewBackend($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the title / name of the category
     *
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        try {
            $Field = $this->getField(Fields::FIELD_TITLE);
            $data  = $Field->getValue();

            if (isset($data[$current])) {
                return $data[$current];
            }
        } catch (QUI\Exception $Exception) {
        }

        QUI\System\Log::addWarning(
            QUI::getLocale()->get(
                'quiqqer/products',
                'warning.product.have.no.title'
            ),
            array(
                'lang' => $current,
                'id' => $this->getId()
            )
        );

        return '#' . $this->getId();
    }

    /**
     * @return QUI\ERP\Products\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Products\Price(
            $this->getAttribute('price'),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes          = parent::getAttributes();
        $attributes['id']    = $this->getId();
        $attributes['title'] = $this->getTitle();

        // fields
        $fields    = array();
        $fieldList = $this->getFields();

        /* @var $Field Field */
        foreach ($fieldList as $Field) {
            $fields[] = $Field->toProductArray();
        }

        if (!empty($fields)) {
            $attributes['fields'] = $fields;
        }

        return $attributes;
    }

    /**
     * save the data
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('product.edit');

        $fieldData = array();
        $fields    = $this->getFields();

        /* @var $Field Field */
        foreach ($fields as $Field) {
            if ($Field->isRequired()) {
                $Field->validate($Field->getValue());
            }

            $fieldData = $Field->toProductArray();
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array(
                'productNo' => $this->getAttribute('productNo'),
                'data' => json_encode($fieldData)
            ),
            array('id' => $this->getId())
        );
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('product.delete');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('id' => $this->getId())
        );
    }

    /**
     * Field methods
     */

    /**
     * Return the product fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Return the field
     *
     * @param integer $fieldId
     * @return Field
     * @throws QUI\Exception
     */
    public function getField($fieldId)
    {
        if (isset($this->fields[$fieldId])) {
            return $this->fields[$fieldId];
        }

        throw new QUI\Exception(array(
            'quiqqer/products',
            'exception.field.not.found',
            array(
                'fieldId' => $fieldId,
                'productId' => $this->getId()
            )
        ));
    }

    /**
     * Return the field value
     *
     * @param integer $fieldId
     * @return mixed
     * @throws QUI\Exception
     */
    public function getFieldValue($fieldId)
    {
        return $this->getField($fieldId)->getValue();
    }
}
