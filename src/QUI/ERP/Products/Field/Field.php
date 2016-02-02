<?php

/**
 * This file contains QUI\ERP\Products\Field\Modell
 */
namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class Field
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID );
 */
abstract class Field extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Field
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected $id;

    /**
     * Field-Name
     *
     * @var string
     */
    protected $name;

    /**
     * Field value
     *
     * @var mixed
     */
    protected $value = '';

    /**
     * Modell constructor.
     *
     * @param integer $fieldId
     */
    public function __construct($fieldId)
    {
        $this->id = (int)$fieldId;

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
     * Return the view for the backend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    abstract protected function getBackendView();

    /**
     * Return the view for the frontend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    abstract protected function getFrontendView();

    /**
     * Return the view
     *
     * @return \QUI\ERP\Products\Field\View
     */
    public function getView()
    {
        switch ($this->getAttribute('viewType')) {
            case 'backend':
                return $this->getBackendView();
                break;

            default:
                return $this->getFrontendView();
        }
    }

    /**
     * Return Field-ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * saves / update the field
     */
    public function save()
    {
        $this->getController()->save();
    }

    /**
     * Delete the field
     */
    public function delete()
    {
        $this->getController()->delete();
    }

    /**
     * Set the field name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the field value
     *
     * @param mixed $value
     * @throws QUI\Exception
     */
    public function setValue($value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Return the field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
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
                'products.field.' . $this->getId() . '.title'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $type = $this->getType();
        $type = str_replace('QUI\ERP\Products\Field\Types\\', '', $type);

        $attributes          = parent::getAttributes();
        $attributes['id']    = $this->getId();
        $attributes['title'] = $this->getTitle();
        $attributes['type']  = $type;

        return $attributes;
    }

    /**
     * @return array
     */
    public function toProductArray()
    {
        return array(
            'id' => (string)$this->getId(),
            'value' => $this->getValue()
        );
    }
}
