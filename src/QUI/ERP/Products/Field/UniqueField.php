<?php

/**
 * This file contains QUI\ERP\Products\Field\UniqueField
 */
namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class UniqueField
 * This field is a field for the view, unique fields are not editable
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID )->createUniqueField();
 */
class UniqueField implements QUI\ERP\Products\Interfaces\UniqueField
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected $id;

    /**
     * Field name
     *
     * @var string
     */
    protected $name;

    /**
     * Field title
     * @var string|array
     */
    protected $title;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var string
     */
    protected $suffix = '';

    /**
     * @var integer
     */
    protected $priority;

    /**
     * Field value
     *
     * @var mixed
     */
    protected $value = '';

    /**
     * @var string
     */
    protected $type;

    /**
     * is customfield?
     *
     * @var bool
     */
    protected $custom;

    /**
     * @var bool
     */
    protected $isSystem = false;

    /**
     * @var bool
     */
    protected $isStandard = false;

    /**
     * @var bool
     */
    protected $isRequire = false;

    /**
     * @var bool
     */
    protected $unassigned = false;

    /**
     * Is the field a product own field
     * @var bool
     */
    protected $ownField = false;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Model constructor.
     *
     * @param integer $fieldId
     * @param array $params - optional, field params (system, require, standard)
     */
    public function __construct($fieldId, $params = array())
    {
        $this->id = (int)$fieldId;

        $attributes = array(
            'title',
            'type',
            'prefix',
            'suffix',
            'priority',
            'options',
            'isRequired',
            'isStandard',
            'isSystem',
            'custom',
            'unassigned',
            'value',
            'ownField'
        );

        foreach ($attributes as $attribute) {
            if (!isset($params[$attribute])) {
                continue;
            }

            if (property_exists($this, $attribute)) {
                $this->$attribute = $params[$attribute];
            }
        }
    }

    /**
     * Return the view
     *
     * @return View
     */
    public function getView()
    {
        $type = $this->getType();

        if (defined('QUIQQER_BACKEND')) {
            $viewClass = 'QUI\ERP\Products\Field\Types\\' . $type . 'BackendView';
        } else {
            $viewClass = 'QUI\ERP\Products\Field\Types\\' . $type . 'FrontendView';
        }

        if (class_exists($viewClass)) {
            return new $viewClass($this->getAttributes());
        }

        return new View($this->getAttributes());
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
     * Change the price of the product
     * Returns the price object
     *
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Price    = new QUI\ERP\Products\Utils\Price(0, $Currency);

        return $Price;
    }

    /**
     * Return the type of the parent field
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Is the field unassigned?
     *
     * @return bool
     */
    public function isUnassigned()
    {
        return $this->unassigned;
    }

    /**
     * @return bool
     */
    public function isCustomField()
    {
        return $this->custom;
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
     * @return array|string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return the title of the field
     * The title are from the user and translated
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
        return array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'type' => $this->getType(),
            'options' => $this->getOptions(),
            'isRequired' => $this->isRequired(),
            'isStandard' => $this->isStandard(),
            'isSystem' => $this->isSystem(),

            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'priority' => $this->priority,
            'custom' => $this->isCustomField(),
            'unassigned' => $this->isUnassigned(),
            'value' => $this->getValue()
        );
    }

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem()
    {
        return $this->isSystem;
    }

    /**
     * @return bool
     */
    public function isStandard()
    {
        return $this->isStandard;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->isRequire;
    }

    /**
     * @return bool
     */
    public function isOwnField()
    {
        return $this->ownField;
    }
}
