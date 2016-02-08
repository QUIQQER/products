<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;

/**
 * Class Controller
 * Product Model
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Product extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
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
        $this->getController()->load();

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }
    }

    /**
     * @return Controller
     */
    protected function getController()
    {
        return new Controller($this);
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
            return QUI::getLocale()->get(
                'quiqqer/products',
                'products.product.' . $this->getId() . '.title'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.product.' . $this->getId() . '.title'
        );
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

        return $attributes;
    }

    /**
     * save the data
     */
    public function save()
    {
        $this->getController()->save();
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        $this->getController()->delete();
    }

    /**
     * Field methods
     */

    /**
     * @param Field $Field
     */
    public function addField(Field $Field)
    {
        $this->fields[$Field->getId()] = $Field;
    }

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
     * @param Field $Field
     */
    public function getFieldValue(Field $Field)
    {
        // TODO: Implement getFieldValue() method.
    }
}
